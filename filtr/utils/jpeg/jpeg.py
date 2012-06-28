"""Extract and save a JPG comments or exif segment """

## JPEG data is divided into segments, each of which starts with a 2-byte marker.
## The first byte of each marker is FF. The second byte defines the type of marker.
## In a header the marker is immediately followed by two bytes that indicate the length of the information, 
## in bytes, that the header contains.  
## The two bytes that indicate the length are always included in that count.
##
## ## To allow for recovery in the presence of errors, it must be possible to detect markers without 
## ## decoding all of the intervening data. Hence markers must be unique. 
## ## To achieve this, if an FF byte occurs in the middle of a segment, an extra 00 stuffed byte is 
## ## inserted after it and 00 is never used as the second byte of a marker. 

## Usefull Markers:
## SOI  - start of image         (FFD8) - if file does not start with this, it's not JPEG
## APP0                          (FFE0)
## APP1 - EXIF segment           (FFE1) - metadata like: description, date/time taken, author, camera details
## APP2 - EXIF extension         (FFE2)   photo quality details, GPS details, thumbnail etc 
##                                      - many cameras store info here, some apps store to, more just read
##                                      - it is however a kind of complex data structure
## COM  - comments segment       (FFFE) - I'm not sure how many applications use this, Photoshop doesn't seem to
##                                      - Jpegs can have multiple comments, but for now only read
##                                        the first one (most jpegs only have one anyway). Comments
##                                        are simple single byte ISO-8859-1 strings.
## APP13 - IPTC segment          (FFED) - another metadata segment (Photoshop & Picasa image caption)
##                                      - captions, keywords, people, ratings, etc.
## SOF  - Start of Frame segment (FFC0) - the actual image

import util
import exif
import iptc
import urllib2
import StringIO


__version__ ="0.1.5"
__license__ = "python"

DEBUG = False

class NoImageFound(Exception): pass
class NoJPEGFound(Exception): pass

class ImgObj:
    """
        A image encapsulator, either a StringIO or a file object
        - if a url is given, result will be in a StrinIO object
            - after a write (setExif?) you may want to do something with the returned content to persist,
              for example write into a file or upload to an url, or email etc.
        - if fileName or file object is given, the file object is returned
            - after a write (setExif?), the file will be closed and containing new value
    """
    def __init__(self, img):
        if isinstance(img, basestring):
            if img.lower().startswith("http://") or img.lower().startswith("https://"):
                self.file = StringIO.StringIO(urllib2.urlopen(img).read())
                self.type = "url"
                self.fileName = img;
            else:
                self.file = open(img, "rb")
                self.type = "file name"
                self.fileName = img
        elif isinstance(img, file):
            self.file = img
            self.type = "file"
            self.fileName = self.file.name
        else:
            raise NotImplemented("type of %s is not supported" % type(fileObj))

    def __getattr__(self, name):
        if name=="close" and self.type=="url":
            #when done with _write we return a closed file or StrinIO positioned at begining of stream
            return self.__posZero
        return getattr(self.file, name)

    def reset(self):
        if self.type == "url":
            self.file = StringIO.StringIO()
        elif self.type in ("file name", "file"):
            self.file.close()
            self.file = open(self.fileName, "wb")

    def __posZero(self):
        self.file.seek(0)
        

def _read(file, marker):
    "return value of <marker> segment if exists, or None otherwise"
    segmentValue=None
    try: markerSeg, sof, length, im = _process(file, marker)
    except: return ""
    if markerSeg:
        im.seek(-2, 1)  #retract right after marker ID
        segmentValue = im.read(util.getNr(length))[2:]
    im.close()
    if not markerSeg and not sof:
        raise NoImageFound, "There is no image in this image file ?"
    return segmentValue


def _write(value, file, marker):
    """-Overwrights <marker> segment with given <value>
       -if <marker> segment does not already exist then it will write it 
        right before the image segment (SOF - FFC0)
    """
    markerSeg, sof, length, im = _process(file, marker)
    if markerSeg or sof:
        lenHex = util.setNr(len(value)+2, "short")  #the length on 2 bytes
        segment = "\xFF" + marker + lenHex + value  #segment = marker + value length + value
        
        pos = im.tell() - 4 
        im.seek(0)
        before = im.read(pos)
        if markerSeg: 
            im.seek(util.getNr(length) + 2, 1)      #skip over existing segment, including marker
        after = im.read()
        im.reset()
        im.write(before)
        im.write(segment)
        im.write(after)
        im.close()
        return im
    else:
        im.close()
        raise NoImageFound, "There is no image in this image file ?"


def _process(file, target):
    """seek target marker in JPEG file, and return tuple:
    (found marker segment boolean, reached image segment boolean, marker segment length, 
    the open file object positioned at the begining of value)
    """
    comment = image = False
    im = ImgObj(file)
    marker = im.read(2)
    if marker != "\xFF\xD8":
        raise NoJPEGFound, "Not a JPEG image"
    l=2
    while im.read(1) == "\xFF":
        markerType = im.read(1)
        length = im.read(2)
        l += util.getNr(length) + 2
        if markerType == "\xC0": #SOF - got to the image, stop
            return (False, True, length, im) 
        if markerType == target: 
            return (True, False, length, im) 
        #skip over current segment 
        #-2 to move <length> positions starting right after marker
        im.seek(util.getNr(length) - 2, 1)  
    return (False, False, length, im) 
     
# COM segment - FFFE - a segment to be used for whatever 
def getComments(file):
    "read comments (\xFF\xFE COM segment) from a JPEG file"
    com = _read(file, "\xFE")
    if com is not None:
        return com
    return ""

def setComments(txt, file):
    "write a comment (\xFF\xFE COM segment) into a JPEG file"
    return _write(txt, file, "\xFE")

def getExif(file):
    "return FFE1 Exif segment (APP1) from a JPEG file, wrapped into an Exif instance object"
    exifSeg = _read(file, "\xE1")
    if not exifSeg in (None, ""):
        return exif.Exif(exifSeg, "\xE1")
    else:
        return getExif2(file)
    
def setExif(exif, file):
    """ - write an exif (\xFF\xE1 OR \xFF\xE2 Exif segment depending where it was read from)
          segment into a JPEG file,
        - <exif> must be an Exif instance object
    """
    return _write(exif.binary(), file, exif.jpegMarker)

def getExif2(file):  #extended
    "return FFE2 extended Exif segment (APP2) from a JPEG file, wrapped into an Exif instance object"
    exifSeg = _read(file, "\xE2")
    if not exifSeg in (None, ""):
        return exif.Exif(exifSeg, "\xE2")
    

# Scan through the markers looking for the APP13 (\xFF\xED) marker, where IPTC/IIM data should be
# found. While this isn't a formally defined standard, all programs have (supposedly) adopted
# Adobe's technique of putting the data in APP13.
def getIPTC(file):
    "return FFED IPTC segment (APP13) from a JPEG file, wrapped into an IPTC instance object"
    iptcSeg = _read(file, "\xED")
    if not iptcSeg in (None, ""):
        return iptc.IPTC(iptcSeg)

def setIPTC(iptc, file):
    return _write(iptc.binary(), file, "\xED")


def test(debug=True):
    global DEBUG
    debugorig = DEBUG
    DEBUG = debug
    import os
    folder = r"C:\test"
    files = [file for file in os.listdir(folder) if file.lower().endswith(".jpg")]
    tags = {}
    for file in files:
        print file.ljust(40)
        try:
            e1 = getExif(os.path.join(folder, file))
            e2 = getExif2(os.path.join(folder, file))
        except Exception, e:
            print str(e) 
            continue
        do = []
        if e1 is not None:
            print "has exif", 
            do.append(e1)
        if e2 is not None:
            print "has exif2", 
            do.append(e2)
        cnt=0
        for e in do:
            for ifd in e.ifds:
                for tag in ifd:
                    cnt += 1
                    id = tag.niceID()
                    old = tags.get(id, (0,0,0))
                    if e is e1:
                        tags[id] = (old[0] + 1, old[1] + 1, old[2])
                    else:
                        tags[id] = (old[0] + 1, old[1], old[2] + 1)
        print cnt
    keys = [(tags[k][0], k) for k in tags]
    keys.sort()
    keys.reverse()
    keys = [k for _,k in keys]
    print "decending sorted tags, by count"
    for key in keys:
        print key.ljust(30), "count", tags[key][0], "e1 count", tags[key][1], "e2 count", tags[key][2]
    DEBUG = debugorig
    


##   http://www.cb1.com/~john/computing/emacs/lisp/graphics/jpeg-mode.el
##   jpeg-marker-SOF0                     ; M_SOF0  = 0xc0,
##   jpeg-marker-SOF1                     ; M_SOF1  = 0xc1,
##   jpeg-marker-SOF2                     ; M_SOF2  = 0xc2,
##   jpeg-marker-SOF3                     ; M_SOF3  = 0xc3,
##   jpeg-marker-DHT                      ; M_DHT   = 0xc4,
##   jpeg-marker-SOF5                     ; M_SOF5  = 0xc5,
##   jpeg-marker-SOF6                     ; M_SOF6  = 0xc6,
##   jpeg-marker-SOF7                     ; M_SOF7  = 0xc7,
##   jpeg-marker-JPG                      ; M_JPG   = 0xc8,
##   jpeg-marker-SOF9                     ; M_SOF9  = 0xc9,
##   jpeg-marker-SOF10                    ; M_SOF10 = 0xca,
##   jpeg-marker-SOF11                    ; M_SOF11 = 0xcb,
##   jpeg-marker-DAC                      ; M_DAC   = 0xcc,
##   jpeg-marker-SOF13                    ; M_SOF13 = 0xcd,
##   jpeg-marker-SOF14                    ; M_SOF14 = 0xce,
##   jpeg-marker-SOF15                    ; M_SOF15 = 0xcf,
##
##   jpeg-marker-RST0                     ; M_RST0  = 0xd0,
##   jpeg-marker-RST1                     ; M_RST1  = 0xd1,
##   jpeg-marker-RST2                     ; M_RST2  = 0xd2,
##   jpeg-marker-RST3                     ; M_RST3  = 0xd3,
##   jpeg-marker-RST4                     ; M_RST4  = 0xd4,
##   jpeg-marker-RST5                     ; M_RST5  = 0xd5,
##   jpeg-marker-RST6                     ; M_RST6  = 0xd6,
##   jpeg-marker-RST7                     ; M_RST7  = 0xd7,
##   jpeg-marker-SOI                      ; M_SOI   = 0xd8,
##   jpeg-marker-EOI                      ; M_EOI   = 0xd9,
##   jpeg-marker-SOS                      ; M_SOS   = 0xda,
##   jpeg-marker-DQT                      ; M_DQT   = 0xdb,
##   jpeg-marker-DNL                      ; M_DNL   = 0xdc,
##   jpeg-marker-DRI                      ; M_DRI   = 0xdd,
##   jpeg-marker-DHP                      ; M_DHP   = 0xde,
##   jpeg-marker-EXP                      ; M_EXP   = 0xdf,
##
##   jpeg-marker-APP0                     ; M_APP0  = 0xe0,
##   jpeg-marker-APP1                     ; M_APP1  = 0xe1,
##   jpeg-marker-APP2                     ; M_APP2  = 0xe2,
##   jpeg-marker-APP3                     ; M_APP3  = 0xe3,
##   jpeg-marker-APP4                     ; M_APP4  = 0xe4,
##   jpeg-marker-APP5                     ; M_APP5  = 0xe5,
##   jpeg-marker-APP6                     ; M_APP6  = 0xe6,
##   jpeg-marker-APP7                     ; M_APP7  = 0xe7,
##   jpeg-marker-APP8                     ; M_APP8  = 0xe8,
##   jpeg-marker-APP9                     ; M_APP9  = 0xe9,
##   jpeg-marker-APP10                    ; M_APP10 = 0xea,
##   jpeg-marker-APP11                    ; M_APP11 = 0xeb,
##   jpeg-marker-APP12                    ; M_APP12 = 0xec,
##   jpeg-marker-APP13                    ; M_APP13 = 0xed,
##   jpeg-marker-APP14                    ; M_APP14 = 0xee,
##   jpeg-marker-APP15                    ; M_APP15 = 0xef,
##
##
##   jpeg-marker-JPG0                     ; M_JPG0  = 0xf0,
##   jpeg-marker-JPG1                     ; M_JPG1  = 0xf1,
##   jpeg-marker-JPG2                     ; M_JPG2  = 0xf2,
##   jpeg-marker-JPG3                     ; M_JPG3  = 0xf3,
##   jpeg-marker-JPG4                     ; M_JPG4  = 0xf4,
##   jpeg-marker-JPG5                     ; M_JPG5  = 0xf5,
##   jpeg-marker-JPG6                     ; M_JPG6  = 0xf6,
##   jpeg-marker-JPG7                     ; M_JPG7  = 0xf7,
##   jpeg-marker-JPG8                     ; M_JPG8  = 0xf8,
##   jpeg-marker-JPG9                     ; M_JPG9  = 0xf9,
##   jpeg-marker-JPG10                    ; M_JPG9  = 0xfa,
##   jpeg-marker-JPG11                    ; M_JPG9  = 0xfb,
##   jpeg-marker-JPG12                    ; M_JPG9  = 0xfc,
##   jpeg-marker-JPG13                    ; M_JPG13 = 0xfd,
##   jpeg-marker-COM                      ; M_COM   = 0xfe,