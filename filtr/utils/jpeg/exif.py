"""Edit a JPG Exif segment via an Exif instance """

## http://exif.org/
## EXIF segment looks like this:
## 2 bytes JPEG EXIF marker
## 2 bytes length
## 2 bytes EXIF identifier code
## TIFF header
## then there are 2 IFD's (1 for metadata, one for thumbnail)
## 0 IFD - metadata      (not so easy, there is actualy exif ifd, gps ifd ... )
## 0 IFD Value
## 1 IFD - thumbnail
## 1 IFD value 
##
## TIFF Header:
##     2 bytes (either 4949 (little endian) or 4D4D (big endian)
##     002A
##     4 bytes as offset to 0 IFD (if IFD is right after TIFF Header the is 00000008)
##     
## 0 IFD looks like this:
##    1. 2 bytes counting the number of tags
##    2. TAGS (12 bytes each)
##        a. 2 bytes tag
##        b. 2 bytes type
##        c. 4 bytes count
##        d. 4 bytes value offset
##    3. 4 bytes as an offset to the next IFD (1 IFD) 
##    
##    TAGS details:
##        b. type may be a number representing:
##           1=byte 
##           2=ascii (1 byte , and NULL (\x00) terminated string)
##           3=short (2 bytes unsigned int)
##           4=long  (4 bytes unsigned int)
##           5=rational (2 longs, first=numerator second=denuminator)
##           7=undefined (1 byte)
##           see http://exif.org/Exif2-1.PDF pag 24 for all
##        c. number of values (==number of <type> structures) ex. 1 short (2 bytes) or 
##                                                                2 short (4 bytes) or 
##                                                                2 ascii (2 bytes) etc
##        d. - usualy is an offset from the start of TIFF header to the value segment
##           - if value is smaller then 4 bytes then the value is stored right here

from __future__ import division
import util

DEBUG = False

class Tag:
    #see comments above about tags details
    typeMap = {1:1, 2:1, 3:2, 4:4, 5:8, 6:1, 7:1, 8:2, 9:4, 10:8}
    
    def __init__(self, tag, parent):
        if DEBUG:
            print repr(tag)
        self.raw = tag
        self.parent = parent
        self.endian = parent.endian
        tiff = parent.tiff
        self.id = tag[:2]
        self.intID = util.getNr(self.id, self.endian)
        self.type = util.getNr(tag[2:4], self.endian)
        self.count = util.getNr(tag[4:8], self.endian)
        try:
            self.bytes = self.typeMap[self.type]
            self.len = self.bytes * self.count
        except:
            raise ValueError, "Value type %s is not supported for %s - %s" % (self.type, self.niceID(), repr(tag))

        if self.len <= 4:
            self.valueFlat = tag[8:]  #as it needs to be written back into the file
            self.value = self._getVal(tag[8:])
            self.originalOffset = None
        else:
            offset = util.getNr(tag[8:], self.endian)
            self.originalOffset = offset
            self.valueFlat = tiff[offset: offset + self.len]
            self.value = self._getVal(tiff[offset: offset + self.len])

        if (self.len>len(self.valueFlat)):
            print "LEN > valueFlat", self.niceID(), self.len
            self.len = len(self.valueFlat)
            self.count = self.len / self.bytes
            if self.count <= 0: self.count = 1
        
        if DEBUG:
            self.display()
    
    def display(self):
        val = self.getValue()
        if val != self.value:
            val = repr(self.value) + " - " + val
        print "(%s), type: %s, count:%s, value:%s" % (self.niceID(), self.type, self.count, repr(val))
        
    # TODO: add support for all types
    def _getVal(self, val):      #from binary to python
        if self.type in [1,3,4]: #byte, sort, long
            if len(val)> self.len:
                #val = self.endian == "II" and val[:self.bytes] or val[self.bytes:]
                #for kodak where endian is MM the significant bytes are still the first ones
                val = val[:self.bytes]   #correct is like this regardless of endian
            r = [util.getNr(''.join(t), self.endian) for t in util.unzip(val, self.bytes)]
            if len(r)==1: return r[0]
            return r
        if self.type in (5,10):  #rational
            r = util.unzip([util.getNr(''.join(t), self.endian) for t in util.unzip(val, 4)], 2)
            if len(r)==1: return r[0]
            return r
        if self.type == 2: #string
            return val[:-1]   #strip NULL from NULL terminated string
        return val  # unknown
    
    def setValue(self, val):    #from python to binary
        #we support writing unsigned (short, long, rational), ascii
        self.value = val
        if self.type in [3,4]: #short, long
            if type(val) not in [[], ()]:
                val = [val]
            self.valueFlat = ''.join([util.setNr(n, self.bytes, self.endian) for n in val])
            self.count = len(val)
        elif self.type in [5,10]: #rational
            self.valueFlat = ''.join([util.setNr(n, 4, self.endian) + util.setNr(d, 4, self.endian) for n,d in self.value])
            self.count = 1
        elif self.type == 2:  #ascii
            self.valueFlat = self.value + "\x00"   #must be NULL terminated string
            self.count = len(self.value) + 1
            if self.count < 4:
                self.valueFlat = self.valueFlat.rjust(4)    #self.valueFlat + ("\x00" * (4-len(self.valueFlat)))
                self.count = 4
        else:
            if DEBUG: print "set raw val", self.niceID()
            self.valueFlat = self.value
            self.count = len(self.value)
            # Raw values must be at least 4 bytes long. If padding is needed to
            # bring them to 4 bytes, they are always left justified regardless of
            # endian-ness
            if self.count < 4:
                self.valueFlat = self.valueFlat + ("\x00" * (4-len(self.valueFlat)))
                self.count = 4
        self.len = self.bytes * self.count
    
    def getValue(self, parse=True):
        "return tag value, if posible parsed to a nice representation"
        if not parse:
            return self.value
        tag = self.id
        if self.endian == "II":
            tag = util.reverse(tag)
        val =  exif_tags_description.get(tag, None)
        if val is None: 
            return self.value
        if type(val) == type(()):
            return val[1].get(self.value, self.value)
        return self.value
    
    def niceID(self):
        "return tag hex value and also a description if available"
        id = exif_tags_description.get(self.endian == "MM" and self.id or util.reverse(self.id), None)
        if id == None: 
            id = hex(util.getNr(self.id, self.endian))
        else:
            id = hex(util.getNr(self.id, self.endian)) + " - " + (type(id) == type(()) and id[0] or id)
        return id
    
class FirstTag(Tag):  #when we need to add a tag
    def __init__(self, tag, value, type, parent):
        self.parent = parent
        self.endian = parent.endian
        self.originalOffset = None
        if isinstance(tag, int):
            self.intID = tag
            self.id = util.setNr(tag, 2)
        else:
            self.intID = util.getNr(tag)
            self.id = tag
        if self.endian == 'II':
            self.id = util.reverse(self.id)
        #self.id = self.endian == "II" and util.reverse(tag) or tag
        self.type = type
        self.bytes = self.typeMap[self.type]
        self.setValue(value)
        if DEBUG: print "create TAG", tag.id


class Exif(object):
    """ - read/write proprieties: description, software, artist, originalDate, 
          comments, camera, cameraMake, cameraModel
       
       - read proprieties: pixelsResolution, imageSize, date,
         rawMakerNotes (unparsed), makerNotes (parsed and interpreted), parsedMakerNotes (raw but parsed)
         xyResolution, brightness, flash, lightSource, subjectDistance, exposure, focalLength,
         thumbnail
       
       - raw methods to add advanced flexibility:
           -get(tagID) e.q get('\x01\x0e')
           -set(tagID, value, context, type):
               - contexts: self.ifd0, self.exif, self.interop or self.gps
               - type one of 2=ascii,3=short,4=long,5=rational,7=unknown (exif data type)
               - **Attention !** : if you add to gps or interop and they were empty, you must also 
                 add the pointer tag to this context into ifd0 with a dummy value like 0
    """
    def __init__(self, value=None, jpegMarker='\xE1'):
        if value is None:
            raise "EXIF must not be empty"
        self.jpegMarker = jpegMarker
        self.origValue = value
        self.tiff = value[6:]
        self.endian = value[6:8]
        self.fixed42 = "\x00\x2A"    #42 value[8:10]
        
        #IFD's data structure
        self.ifd0 = []
        self.exif = []
        self.gps = []
        self.interop = []
        self.ifd1 = []
        # IFD1 contains thumbnail and related info. It is important that we parse this even if we
        # don't need the thumbnail, b/c otherwise the offsets will be wrong when we try to rebuild the file
        self.ifds = [self.ifd0, self.exif, self.gps, self.interop, self.ifd1]
        self.IFDsByTag = {34665: self.exif, 34853: self.gps, 40965: self.interop}
        self.thumbnail = None

        if DEBUG: 
            print "endian", self.endian

        #parse IFD's into above data structure:
        self.parse_ifd(util.getNr(value[10:14], self.endian), self.ifd0)
         
    
    def ifdName(self, ifd):
        if ifd is self.ifd0:
            return "IFD0"
        elif ifd is self.exif: 
            return "EXIF"
        elif ifd is self.gps: 
            return "GPS"
        elif ifd is self.interop: 
            return "INTEROPERABILITY"
        elif ifd is self.ifd1:
            return "IFD1"
        return repr(ifd)
    
    def parse_ifd(self, ifdOffset, context):
        ifd = self.tiff[ifdOffset:]
        self.parse_exif_segment(ifd, context)
    
    def parse_exif_segment(self, ifd, context):
        """ Construct a modifiable Exif.
            Get nr of tags and parse each tag. If a pointer tag to other ifd is found, recurse inside that ifd as well
        """
        thumbStart = 0
        thumbLength = 0
        thumbStripOffset = 0
        nrTags = util.getNr(ifd[:2], self.endian)
        if DEBUG: 
            print self.ifdName(context), "nr tags", nrTags
        offset = 2  #position after nr tags bytes
        for i in range(nrTags):
            try:
                tag = Tag(ifd[offset : offset+12], self)
            except Exception, e:  #mainly unsuported tag types, etc
                # TODO: if a tag is buggy is it ok to just ignore it and go on or should we crash alltoghether
                if DEBUG: 
                    print "Can't create tag", str(e)
                offset += 12
                continue
            
            #this are in IFD1
            if tag.intID==513: #513=JPEGInterchangeFormat; really means offset to thumbnail SOI
                thumbStart = tag.value
            elif tag.intID==514: #514=JPEGInterchangeFormatLength; really means thumbnail length
                thumbLength = tag.value
            elif tag.intID==273: #273=StripOffset - this is only used if the thumbnail is uncompressed (it is possible for thumbnail to be uncompressed TIFF while the main image is compressed JPEG)
                thumbStripOffset = tag.value

            context.append(tag)
            
            #parse -> 0x8769, EXIFOffset | 0xA005, Interopelability IFD offset | 0x8825, GPSOffset
            if context in (self.ifd0, self.exif, self.gps, self.interop) and tag.intID in self.IFDsByTag.keys():
                self.parse_ifd(tag.value, self.IFDsByTag[tag.intID])
            
            offset += 12
        
        if context is self.ifd0:   #do pointer to ifd1 context (last 4 bytes of ifd0)
            ifd1Offset = ifd[offset : offset+4]
            if ifd1Offset == ("\x00" * 4):
                self.ifd1 = []
            else: 
                #self.ifd1 = self.tiff[util.getNr(ifd1Offset, self.endian):]
                self.parse_ifd(util.getNr(ifd1Offset, self.endian), self.ifd1)
            offset += 4
        elif context is self.ifd1:
            if thumbLength and thumbStart:
                self.thumbnail = self.tiff[thumbStart: thumbStart + thumbLength]
            elif thumbStripOffset:
                self.thumbnail = self.tiff[thumbStripOffset:]
            #even if thumbnail is corrupted we still write it back
            #if self.thumbnail:
            #    assert self.thumbnail[:2] == '\xff\xd8'  #SOI
            #    assert self.thumbnail[-2:] == '\xff\xd9' #EOF
            
    def binary(self):
        """ return Exif as is needed to be written into the jpeg file
        """
        exifPointer = interopPointer = gpsPointer= thumbnailStripOffsetPointer= thumbnailPointer = None
        
        #endian + fixed number 42 + 8 (ifd0 starts right after tiff header)
        fixed42 = self.endian == "MM" and "\x00\x2A" or "\x2A\x00"
        ifd0offset = self.endian == "MM" and "\x00\x00\x00\x08" or "\x08\x00\x00\x00"
        res = self.endian + fixed42 + ifd0offset
        
        # The Exif indicator doesn't count in the offset. So we begin counting
        # with the TIFF header (endian + fixed 42 + ifd0 offset)
        offset = 8 
        for ifd in self.ifds:
            #this is a come back later to fill in a gap with offsets to ifd, when we know the offset
            if ifd is self.exif and self.exif:
                res = res[:exifPointer] + util.setNr(len(res), 4, self.endian) + res[exifPointer+4:]
            if ifd is self.interop and self.interop:
                res = res[:interopPointer] + util.setNr(len(res), 4, self.endian) + res[interopPointer+4:]
            if ifd is self.gps and self.gps:
                res = res[:gpsPointer] + util.setNr(len(res), 4, self.endian) + res[gpsPointer+4:]
            if ifd is self.ifd1 and self.ifd1:
                res = res[:ifd1Pointer] + util.setNr(len(res), 4, self.endian) + res[ifd1Pointer+4:]
            
            nrTags = len(ifd)
            res += util.setNr(nrTags, 2, self.endian)   #write nr of tags

            tagValOffset = 0
            headLen = 2 + (nrTags * 12) + 4 #nr of tags + len(all tags) + pointer to next IFD

            valOffset = offset + headLen
            if DEBUG: print "value offset", valOffset, " headLen ", headLen
            
            for tag in ifd:
                res +=  tag.id                                #2 bytes  id
                res += util.setNr(tag.type, 2, self.endian)   #4        type
                res += util.setNr(tag.count, 4, self.endian)  #8        value bytes(by type) count
                
                #tag value pointer
                if tag.id == "\x87\x69" or (self.endian=="II" and tag.id == "\x69\x87"): #exif
                    #This is a placeholder, to be replaced later
                    #now just to have correct length
                    exifPointer = len(res)
                    res += util.setNr(0, 4, self.endian)            
                elif tag.id == "\xA0\x05" or (self.endian=="II" and tag.id == "\x05\xA0"): #interopelability
                    interopPointer = len(res)
                    res += util.setNr(0, 4, self.endian)
                elif tag.id == "\x88\x25" or (self.endian=="II" and tag.id == "\x25\x88"):   #gps
                    gpsPointer = len(res)
                    res += util.setNr(0, 4, self.endian)
                elif ifd is self.ifd1 and tag.intID==513:
                    #513=JPEGInterchangeFormat; really means offset to thumbnail SOI
                    thumbnailPointer = len(res)
                    res += util.setNr(0, 4, self.endian)
                elif ifd is self.ifd1 and tag.intID==273:
                    #273=StripOffset - this is only used if the thumbnail is uncompressed (i.e. in TIFF format)
                    thumbnailStripOffsetPointer = len(res)
                    res += util.setNr(0, 4, self.endian)
                elif len(tag.valueFlat) <= 4:
                    res += tag.valueFlat                         #12
                    #print "write flat", tag.niceID(), " -> ", repr(tag.valueFlat)
                else:
                    if DEBUG: print "write offset", tag.niceID(), " -> ", util.setNr(valOffset + tagValOffset, 4, self.endian), " == ", (valOffset + tagValOffset)
                    res += util.setNr(valOffset + tagValOffset, 4, self.endian)    #12
                    if tag.id == "\x92\x7C" or (self.endian=="II" and tag.id == "\x7C\x92"):  #maker note 0x927C
                        mn = self.parsedMakerNotes
                        if mn: #header + number of tags + len(tags header) + len(val of tags >4)
                            tagValOffset += len(mn.header) + 2 + (len(mn.tags)*12) + sum(t.len for t in mn.tags if len(t.valueFlat) > 4)
                            if DEBUG: print "makerNotes parsed len", (len(mn.header) + 2 + (len(mn.tags)*12) + sum(t.len for t in mn.tags if len(t.valueFlat) > 4))
                        else:
                            tagValOffset += tag.len
                            if DEBUG: print "makerNotes raw len", tag.len
                    else:
                        tagValOffset += tag.len
                    
            #done with ifd tag declarations, now we go on to ifd tags values

            #before we go on to value if this ifd is ifd0 we need to add a pointer to ifd1 in this last
            #4 bytes of ifd0 tags declaration
            if ifd is self.ifd0:
                ifd1Pointer = len(res)  #pointer to ifd1
                res += util.setNr(0, 4, self.endian)
            else:
                # NextIFD offset is only used for IFD0 to point to IFD1, otherwise we still must
                # fill this last 4 bytes with 0
                res += "\x00" * 4 

            #now write the values
            for tag in ifd:
                if len(tag.valueFlat) > 4:
                    if tag.id == "\x92\x7C" or (self.endian=="II" and tag.id == "\x7C\x92"):  #maker note 0x927C
                        #handle maker note by rewriting the pointers to their values as offest from the begining of tiff
                        mn = self.parsedMakerNotes
                        if mn:
                            mnRes = mn.header + util.setNr(len(mn.tags), 2, self.endian) #first we write header + number of tags
                            mnTagValOffset = len(res) + len(mnRes) + (len(mn.tags)*12)   #current offset + (header + nr of tags) + (tag declarations)
                            #same alghorithm as bove one more time.
                            #1. First tags declaration with pointers
                            for t in mn.tags:
                                mnRes +=  t.id                                #2 bytes
                                mnRes += util.setNr(t.type, 2, self.endian)   #4
                                mnRes += util.setNr(t.count, 4, self.endian)  #8
                                if len(t.valueFlat) <= 4:
                                    mnRes += t.valueFlat                      #12
                                else:
                                    mnRes += util.setNr(mnTagValOffset, 4, self.endian)      #12
                                    mnTagValOffset += t.len
                            #2. Then tags values
                            for t in mn.tags:
                                if len(t.valueFlat) > 4:
                                    try: mnRes += t.valueFlat
                                    except UnicodeDecodeError, e:
                                        mnRes += t.valueFlat.encode("utf-8")
                            if DEBUG: print "write >4 value parsed_MN -> ", tag.niceID(), " offset: ", len(res), " -> ", repr(mnRes)
                            res += mnRes  #done, we can now add the new maker notes with the correct pointers
                        else:
                            #failed to parse MakerNotes maybe because is not in Exif IFD style format
                            #then just write it as is and if they use offets relative to the beging
                            #of file and not the tag itself too bad for them
                            #For example Kodak maker notes are not in standard IFD format
                            if DEBUG: print "write >4 value unparsed_MN -> ", tag.niceID(), " offset: ", len(res), " -> ", repr(self.rawMakerNotes)
                            res += self.rawMakerNotes
                    else:
                        if DEBUG: print "write >4 value", tag.niceID(), " offset: ", len(res), " -> ", repr(tag.valueFlat)
                        try: res += tag.valueFlat
                        except UnicodeDecodeError, e:
                            if DEBUG: print "write >4 value", tag.niceID(), " -> unicode"
                            res += tag.valueFlat.encode("utf-8")

            #Tack on the thumbnail to the end of IFD1:
            if ifd is self.ifd1:
                if thumbnailPointer:
                    res = res[:thumbnailPointer] + util.setNr(len(res), 4, self.endian) + res[thumbnailPointer+4:]
                if thumbnailStripOffsetPointer:
                    res = res[:thumbnailStripOffsetPointer] + util.setNr(len(res), 4, self.endian) + res[thumbnailStripOffsetPointer+4:]
                if self.thumbnail:
                    res += self.thumbnail

            offset = len(res)
            
        return "Exif\x00\x00" + res
        
    #SET tag value related methods
    def set(self, tag, value, targetIfd, type_):
        """ - Overwrite <tagID> tag with <value>.
            - If tag does not already exist, build it in the specified <targetIfd>.
            - The type (short, long, ascii, etc. as 3,4,2 etc.) need to be specified since this program will 
              probably never be aware of all posible tags and their specification. You may need to do some
              research with exif.org to find the right type
        """
        tagID = type(tag)==type(1) and util.setNr(tag)[2:] or tag   #nr to str
        for tag in targetIfd:
            if tag.id == tagID or (self.endian == "II" and tag.id == util.reverse(tagID)):
                tag.setValue(value)
                return True
        tag = FirstTag(tagID, value, type_, self)    #don't have it yet, so create one
        targetIfd.append(tag)
        
    #Generic Read INTERFACE
    def display(self):
        "print all succesfully parsed tags and the ifd they belong to"
        for ifd in self.ifds:
            print self.ifdName(ifd)
            for tag in ifd:
                tag.display()
    
    def dict(self):
        "return a dictionary {tag description: (ifd, tag id, value)} of all parsed tags"
        d = {}
        for ifd in self.ifds:
            for tag in ifd:
                id = exif_tags_description.get(self.endian == "MM" and tag.id or util.reverse(tag.id), repr(tag.id))
                id = type(id) == type(()) and id[0] or id
                d[id] = (self.ifdName(ifd), hex(util.getNr(tag.id, self.endian)), tag.value)
        return d
    
    def get(self, tags, value=True, parse_value=True, targetIfd=None):
        "return value for given <tags> (1 tag or a list of tags as number or string hex value in big endian)"
        if type(tags) in (type(""), type(1)):
            tags = [tags] #allow searching multiple tags at once
        tags = [type(t)==type(1) and util.setNr(t)[2:] or t for t in tags]  #nr to str for each tag
        if self.endian == "II":
            tags = [t[1]+t[0] for t in tags]
        res = []
        
        if targetIfd is None:
            targetIfd = self.ifd0 + self.exif + self.gps + self.interop + self.ifd1
            
        for tag in targetIfd:
            if tag.id in tags:
                if value:
                    res.append(tag.getValue(parse_value))
                else:
                    res.append(tag)
                if len(res) == len(tags):
                    break
        
        if len(res) == 1: return res[0]
        if len(res) == 0: return None
        return res
    
    def __iter__(self):
        "cicle through all tags (as Tag instances)"
        res = []
        map(lambda itm: res.extend(itm), self.ifds)
        return iter(res)
    
    #Read/write INTERFACE:
    def getDescription(self): return self.get(0x010E)
    def setDescription(self, txt): return self.set(0x010E, txt, self.ifd0, 2) #type ascii
    
    def getArtist(self): return self.get(0x013B)
    def setArtist(self, txt): return self.set(0x013B, txt, self.ifd0, 2)
    
    def getSoftware(self): return self.get(0x0131)
    def setSoftware(self, txt): return self.set(0x0131, txt, self.ifd0, 2)  #type ascii
    
    def getComments(self): 
        com = self.get(0x9286)
        if com is not None: 
            com = (com[:8], com[8:])   #first 8 bytes represent the type
            if com[0]== "\x41\x53\x43\x49\x49\x00\x00\x00":
                return com[1].strip().replace("\00", "")
        return None
    def setComments(self, txt): 
        #we only set ascii
        txt = "\x41\x53\x43\x49\x49\x00\x00\x00" + txt   
        return self.set(0x9286, txt, self.exif, 7) #type undefined
    
    def getOriginalDate(self): 
        #Original Date the Picture was taken, usualy set by your camera
        r = self.get(0x9003)  
        if not r:
            r = self.get(0x9004) #digitized date (backup ?)
        if r and r.strip() == "0000:00:00 00:00:00":
            return None
        return r
    def setOriginalDate(self, txt): 
        "the date must be in correct EXIF format, YYYY:MM:DD HH:MM:SS"
        self.set(0x9003, txt, self.exif, 2) #type ascii
        return True
    
    def getCameraMake(self): return self.get(0x010F)
    def setCameraMake(self, txt): return self.set(0x010F, txt, self.ifd0, 2)
    def getCameraModel(self): return self.get(0x0110)
    def setCameraModel(self, txt): return self.set(0x0110, txt, self.ifd0, 2)
    def getCamera(self): 
        c  = self.get([0x010F, 0x0110])
        if c:
            return "Maker: %s, Model: %s" % tuple(c)
    def setCamera(self, (make, model)): 
        if make is not None: 
            self.set(0x010F, make, self.ifd0, 2) #type ascii
        if model is not None:
            self.set(0x0110, model, self.ifd0, 2) #type ascii
    
    description = property(fget = getDescription, fset=setDescription)
    software = property(fget = getSoftware, fset=setSoftware) 
    comments = property(fget = getComments, fset=setComments) 
    originalDate = property(fget = getOriginalDate, fset=setOriginalDate, \
                            doc="""Original Date the Picture was taken, usualy set by your camera;
                            If you want to set it yourself, it must be in correct EXIF format, 
                            YYYY:MM:DD HH:MM:SS""") 
    artist = property(fget = getArtist, fset = setArtist)
    camera = property(fget = getCamera, fset = setCamera)
    cameraMake = property(fget = getCameraMake, fset = setCameraMake)
    cameraModel = property(fget = getCameraModel, fset = setCameraModel)
    
    #Read INTERFACE:
    def getPixelsResolution(self): 
        return self.get([0xA002, 0xA003])
    def getDate(self): return self.get(0x0132)  #file date ?
    def getXYResolution(self): 
        x,y,unit = self.get([0x011A, 0x011B, 0x0128], targetIfd = self.ifd0)    #picture not thumbnail?
        if type(unit) in [type(1), type(1.0)]:
            unit = "unit %s: unknown" % unit
        return "%s X %s %s" % (x[0], y[0], unit)
    def getBrightness(self): return self.get(0x9203)
    def getLightSource(self): 
        l = self.get(0x9208)
        if type(l) in [type(1), type(1.0)]:
            l = "type %s: unknown" % l
        return l
    def getFlash(self): 
        f = self.get(0x9209)
        if type(f) in [type(1), type(1.0)]:
            f = "action %s: unknown" % f
        return f
    
    def getExposure(self): 
        "shutter speed + program type"
        et = self.get(0x829A)
        ep = self.get(0x8822)
        if et:
            et = "%s sec" % _numTuple(et)
                
            if ep and type(ep) in [type(1), type(1.0)]:
                ep = "program %s: unknown" % ep
            elif ep:
                ep = "program: %s" % ep
            else:
                ep = "program: unknown"
            
            return "%s, %s" % (et, ep)

    def getAperture(self):
        a = self.get(0x9202)   #aperture
        f = self.get(0x829d)   #FNumber
        if a: a = str(a[0]/a[1])
        else: a="unknown"
        if f: f = " (F" + str(f[0]/f[1]) + ")"
        else: f=""
        return a+f
        
    def getMetteringMode(self):
        return self.get(0x9207)

    def getISO(self):
        iso = self.get(0x8827)
        if not iso:
            try: iso = self.makerNotes.ISO
            except: pass
        if not iso: iso="unknown"
        return iso
        
    #def getShutterSpeed(self):
    #    st = self.get(0x9201)
    #    if st: return _numTuple((st[1],st[0]))  #TODO: I am not sure this is corect
    
    def getSubjectDistance(self): 
        d = self.get(0x9206) 
        if d:
            return "%s m" % _numTuple(d)

    def getFocalLength(self):
        e = self.get(0x920A)
        if e:
            return "%s mm" % _numTuple(e)
    
    aperture = property(fget = getAperture)
    ISO = property(fget = getISO)
    metteringMode = property(fget = getMetteringMode)
    pixelsResolution = property(fget = getPixelsResolution)
    date = property(fget = getDate)
    xyResolution = property(fget = getXYResolution)
    brightness = property(fget = getBrightness)
    lightSource = property(fget = getLightSource)
    flash = property(fget = getFlash)
    exposure = property(fget = getExposure)
    subjectDistance = property(fget = getSubjectDistance)
    focalLength = property(fget = getFocalLength)
    
    #MORE
    def getImageSize(self): return self.get([0x0100, 0x0101])
    imageSize = property(fget = getImageSize)
    
    def getMakerNotes(self): return self.get(0x927C)
    def getParsedMakerNotes(self):
        try: return self.__ParsedMakerNotes
        except:
            if not 'kodak' in self.camera.lower():
                self.__ParsedMakerNotes = IFDStyleMakerNotes(self)
            else:
                self.__ParsedMakerNotes = None
            return self.__ParsedMakerNotes
    def getInterpretedMakerNotes(self):
        try: return self.__InterpretedMakerNotes
        except:
            camera = self.camera.lower()
            if 'canon' in camera:
                import canon as camera
            elif 'olympus' in camera:
                import olympus as camera
                
            if camera != self.camera.lower():
                self.__InterpretedMakerNotes = camera.MakerNotes(self)
            else:
                self.__InterpretedMakerNotes = None
            return self.__InterpretedMakerNotes
        
    #makerNote = property(fget = getMakerNotes)       #older versions
    rawMakerNotes = property(fget = getMakerNotes)
    parsedMakerNotes = property(fget = getParsedMakerNotes)  #this should always be something
    makerNotes = property(fget = getInterpretedMakerNotes)   #this may be None
    
        
        
##Good php implementation of exif including parsing maker notes with descriptons
##    http://www.offsky.com/software/exif/index.php
##Excelent perl implementation doing even non exif ifd standard parsing (like kodak)
##    http://search.cpan.org/~exiftool/Image-ExifTool/
##    http://www.sno.phy.queensu.ca/~phil/exiftool/
class IFDStyleMakerNotes(object):
    def __init__(self, parent):
        self.parent = parent
        self.header = ""
        self.raw = parent.rawMakerNotes
        self.tags = []
        parent.parse_exif_segment(self.raw, self.tags)
        if self.tags==[]:
            #some cameras start the maker notes with a header
            #- canon never
            #- nikon sometime (for example models: E700 E800 E900 E900S E910 E950)
            #- sanyo, fujifilm, olympus alwaise
            parent.parse_exif_segment(self.raw[8:], self.tags)
            self.header = self.raw[:8]

    def get(self, tag):
        tag = type(tag)==type(1) and util.setNr(tag)[2:] or tag   #nr to str
        if self.parent.endian == "II":
            tag = tag[1]+tag[0]
        for t in self.tags:
            if t.id == tag:
                return t

#base class for iterpreted camera maker notes
class InterpretedMakerNotes(object):
    def __init__(self, exif):
        self._exif = exif
        self._raw = exif.parsedMakerNotes

    def attributes(self):
        return [a for a in dir(self) if not a.startswith("_") and not a in ('attributes', 'display', 'dict')]
    
    def display(self):
        for atr in self.attributes():
            print atr, "=", getattr(self, atr)
    
    def dict(self):
        d={}
        for atr in self.attributes():
            d[atr] = getattr(self, atr)
        return d


#################################################################################################
#################################################################################################
def _numTuple(num):     #exif rational number to decimal or rational string
    if len(num)==2 and num[1]!=0:
        if num[0]>=10 and num[1]>=10:
            num=(int(num[0]/10), int(num[1]/10))
        if num[0]/num[1]>1:
            return "%.2f" % (num[0]/num[1])
        return "%s/%s" % (num[0],num[1])
    else:
        return "%s" % str(num) 

## Some Exif Tags
exif_tags ={#WCatalog displayed tags
            'image description':            0x010E,  #(type ascii          count any)
            'user comments':                0x9286,  #(type undefined      count any -> pointer)
            'file modified date':           0x0132,  #(type ascii          count 20) YYYY:MM:DD HH:MM:SS 
            'PixelXDimension':              0xA002, 
            'PixelYDimension':              0xA003, 
            'DateTimeOriginal':             0x9003, 
            'make':                         0x010F,
            'model':                        0x0110,
            'artist':                       0x013B,  #(type ascii          count any)
            'Brightness':                   0x9203,  #APEX
            'Light source':                 (0x9208, {1:'daylight',2:'fluorescent',3:'tungsten', 10:'flash'}),
            'Flash':                        (0x9209, {0:"Did't Fire", 1:"Fired",
                                                      5:  'Fired (strobe return light not detected)',
                                                      7:  'Fired (strobe return light detected)',
                                                      9:  'Fill Fired (Compulsory Flash)',
                                                      13: 'Fill Fired (Compulsory Flash, Return light not detected)',
                                                      15: 'Fill Fired (Compulsory Flash, Return light detected)',
                                                      16: 'Off', 24: 'Auto Off', 25: 'Auto Fired (Auto-Mode)',
                                                      29: 'Auto Fired (Auto-Mode / Return light not detected)',
                                                      31: 'Auto Fired (Auto-Mode / Return light detected)',
                                                      32: 'Not Available',
                                                      65:"Fired - Red Eye",
                                                      69:"Fired - Red Eye, Return light not detected",
                                                      71:"Fired - Red Eye, Return light detected",
                                                      73:"Fired - Red Eye, Compulsory Flash",
                                                      77:"Fired - Red Eye, Compulsory Flash, Return light not detected",
                                                      79:"Fired - Red Eye, Compulsory Flash, Return light detected",
                                                      80:"No Flash",    #added for Canon Powershot S3 IS
                                                      89:"Fired - Red Eye, Auto-Mode",
                                                      93:"Fired - Red Eye, Auto-Mode, Return light not detected",
                                                      95:"Fired - Red Eye, Auto-Mode, Return light detected"
                                                     }),
            'Exposer Time':                 0x829A,  #in secconds
            'Exposure Program':             (0x8822, {1:'manual', 2:'normal', 3:'aperture', 4:'shutter priority', 
                                                      5:'creative', 6:'action', 7:'portrait', 8:'landscape'}),
            'XResolution':                  0x011A,  #rational
            'YResolution':                  0x011B,  #rational
            'Resolution unit':              (0x0128, {1:'Not Absolute', 2:'Inch', 3:'Centimeter'}), #Resolution unit of measure
            'Subject Distance':             0x9206,  #in metters
            'Focal Length':                 0x920A,  #in mm
            'Software':                     0x0131,  # TODO: should WCatalog set this?

            'Aperture':                     0x9202,  #APEX
            'FNumber':                      0x829D,
            'ISOSpeedRatings':              0x8827,
            'Mettering Mode':               (0x9207, {1:'agerage', 2:'center weight', 3:'spot',
                                                      4:'multi spot', 5:'pattern', 6:'partial'}),
            #TO DO Tags?
            'Shutter Speed':                0x9201,  #in APEX (Additive System of photographic exposure)
            'DateTimeDigitized':            0x9004, 
            'ComponentsConfig':             (0x9101, {1: 'Y',2: 'Cb',3: 'Cr',4: 'Red',5: 'Green',6: 'Blue'}),
            'MakerNote':                    0x927C, 
            'Subject Location':             0xA214,
            'ExposureBiasValue':            0x9204, 
            'ExposureIndex':                0xA215,
            'MaxApertureValue':             0x9205, 
            'Image History':                0x9213, 
            'Scene Type':                   (0xA301, {1: 'directly photographed'}),
            'FileSource':                   (0xA300, {3: 'digital camera'}),
            'Color Space':                  0xA001,
            'BatteryLevel':                 0x828F,
            
            #NOT interested in this but we keep them since we put them here before
            'image width':                  0x0100,  #(type short, long    count 1)
            'image length':                 0x0101,  #(type short, long    count 1)
            'ImageUniqueID':                0xA420,  #sadly never seen this
            'SubsecTime':                   0x9290, 
            'SubsecTimeOriginal':           0x9291, 
            'SubsecTimeDigitized':          0x9292, 
            'TimeZoneOffset':               0x882A,  # TODO: I hope TimeZoneOffset is not a pointer to yet another ifd
            'Copyright':                    0x8298,
            'Exif version':                 0x9000,
            'Exif IFD Pointer':             0x8769,
            'GPS IFD Pointer':              0x8825,
            'Interoperability IFD':         0xA005, 
            'FlashPixVersion':              0xA000, 
            'CompressedBitsPerPixel':       0x9102,
            'InteroperabilityVersion':      0x0002,
            'InteroperabilityIndex':        0x0001,
            'RelatedImageWidth':            0x1001,
            'RelatedImageLength':           0x1002,
            'SensingMethod':                0xA217,
            'FocalPlaneXResolution':        0xA20E,
            'FocalPlaneYResolution':        0xA20F,
            'FocalPlaneResolutionUnit':     0xA210
}
exif_tags_description = {}
for key in exif_tags:
    val = exif_tags[key]
    if type(val) == type(()):
        val = (util.setNr(val[0])[2:], val[1])   #MM int
        exif_tags_description[val[0]] = (key, val[1])
    else:
        val = util.setNr(val, 4)[2:]
        exif_tags_description[val] = key
    exif_tags[key] = val










