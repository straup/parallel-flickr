##    APP13 segment   Contents                                  Description
##                    0xFF 0xED                                 APP13 marker
##                    Segment size (2 bytes) excl. marker
##                    Photoshop 3.0\x00                         Photoshop identification string
##                    8BIM segments (see below)
##
##    A JPEG file from Photoshop has various 8BIM (I don't know the real name) headers.
##    The one with the type 0x04 0x04 contains the textual information. The image URL is stored
##    in a different header. That's why it is currently not supported by the demo class.
##    Other headers contain a thumbnail image and other information.
##
##    Photoshop 6 introduced a slight variation in this header segment. Basically the 4 byte padding
##    has been replaced by a header description text of variable length. The updated sample can now
##    handle these files as well.
##
##    8BIM segment  Description
##    Recorsds        8BIM Segment marker (4 bytes)
##                    Segment type (2 bytes)
##                    Zero byte padding (4 bytes)
##                    Segment size (2 bytes excl. marker, type, padding and size)
##                    Segment data
##
##    The 8BIM header with the text is divided by even more headers, prefixed by 0x1C 0x02.
##    These blocks then finally contain the information. Multiple blocks with the same type
##    (e.g. Keywords) form a list.
##
##            0x1C 0x02 segment     Description
##            Datasets            0x1C 0x02 Segment marker (2 bytes)
##                                Segment type (1 byte)
##                                Segment size (2 bytes excl. marker, type and size)
##                                Segment data
##


import util


class IPTC(object):
    """
    """
    
    def __init__(self, value):
        self.originalValue = value
        self.header = "Photoshop 3.0\x00"
        self.records = []
        self._parsed = []
        self.parse(value[len(self.header):])
        for r in self.records:
            for d in r.datasets:
                if d.name:
                    self._makeattr(d)
                    
    def _makeattr(self, d):
        self._parsed.append(d)
        if hasattr(self, d.name):
            d1 = getattr(self, d.name)
            if isinstance(d1, list):
                d1.append(d)
            else:
                d1 = [d1, d]
            setattr(self, d.name, d1)
        else:
            setattr(self, d.name, d)

    def create(self, name, value):
        #TODO: maybe it's not correct to add to first Record
        ds = self.records[0].create(name, value)
        self._makeattr(ds)
                
    def __iter__(self):
        return iter(self._parsed)

    def parse(self, value):   # 8BIM  Records
        if value:
            marker = value[:4]
            type = value[4:6]
            padding = value[6:10]
            length = util.getNr(value[10:12])
            rValue = value[12:12+length]
            self.records.append(Record(marker, type, padding, rValue, self))

            #Skip a NULL (\x00 terminated value) if not even size
            if length % 2 != 0: length += 1
            self.parse(value[12+length:])  

    def _delete(self, dataset):
        self._parsed.remove(dataset)
        atr = getattr(self, dataset.name)
        if isinstance(atr, list):
            atr.remove(dataset)
            if len(atr) == 1:
                setattr(self, atr[0].name, atr[0])
        else:
            delattr(self, dataset.name)
        
    def binary(self):
        res = self.header
        for record in self.records:
            res += record.binary()
        return res

    supported_iptc_attributes = property(fget = lambda s: txt_datasets.keys(), doc="attributes supported via iptc.create(atr, value), iptc.atr, iptc.atr.delete()")
    def display(self):
        supported = self.supported_iptc_attributes
        for atr in self:
            if atr.name in supported:
                print "%s (%s): %s" % (atr.name, atr.nrType, atr.value)
                
    
class NewIPTC(IPTC):
    def __init__(self):
        self.header = "Photoshop 3.0\x00"
        self.records = [Record('8BIM', '\x00\x02', '\x00\x00\x00\x00', None, self)]
        self._parsed = []
        self.create("writer_editor", "jpeg.py IPTC module (emilas.com/jpeg)")


class Record:
    def __init__(self, marker, type, padding, value, iptc):
        self.marker = marker
        self.type = type
        self.padding = padding
        self.originalValue = value
        self.iptc = iptc
        self.datasets = []
        self.parse(value)

    def parse(self, value):  # sub-segments for 8BIM segment
        if value:
            marker = value[:2]
            type = value[2:3]
            length = util.getNr(value[3:5])
            rValue = value[5:5+length]
            self.datasets.append(DataSet(marker, type, rValue, self))

            # Skip a NULL (\x00 terminated value) if not even size
            try:
                if length % 2 != 0 and value[5+length+1]=='\x00':
                    length += 1
            except IndexError, e:
                pass
            self.parse(value[5+length:])

    def delete(self, dataset):
        self.datasets.remove(dataset)
        self.iptc._delete(dataset)

    def create(self, name, value):
        if name in txt_datasets:
            ds = DataSet('\x1c\x02', util.setNr(txt_datasets[name], 1), value, self)
            self.datasets.append(ds)
            return ds
        else:
            s = ["Only the following are supported:"]
            for k in txt_datasets.keys():
                s.append(k)
            raise NotImplementedError("\n".join(s))
        
    def binary(self):
        res = ""
        for ds in self.datasets:
            res += ds.binary()
        length = util.setNr(len(res), 2)
        if len(res) % 2 != 0:
            # Pad with a blank if not even size but let length be as it was
            res += "\x00"
        return self.marker + self.type + self.padding + length + res


class DataSet:
    def __init__(self, marker, type, value, record):
        self.marker = marker
        self.type = type
        self.value = value
        self.record = record
        self.nrType = util.getNr(type)  #nr_datasets dictionary key
        self.name = nr_datasets.get(self.nrType, None)
        

    def __str__(self):
        return str((self.nrType, self .value))
    __repr__ = __str__

    def delete(self):
        self.record.delete(self)
        
    def binary(self):
        #http://www.iptc.org/std/IIM/4.1/specification/IIMV4.1.pdf  (pag 15)
        res = self.marker + self.type

        val = self.value
        #only parse it this way but not write this way
        #if len(val) % 2 != 0:  #Pad with a NULL if not even size but let length be as it was
        #    val += "\x00"

        if len(self.value) < 32767:
            res += util.setNr(len(self.value), 2) + val
        else:
            #lengthOfValueLength + valueLength + value  (4 should be enough for lengthOfValueLength)
            res += util.setNr(4, 2) + util.setNr(len(self.value), 4) + val
        return res

nr_datasets = {
  #0: 'recordVersion',    # skip -- binary data
  #5: 'objectName',
  7: 'editStatus',
  8: 'editorialUpdate',
  10: 'urgency',
  12: 'subjectReference',
  15: 'category',
  20: 'supplementalCategory',
  22: 'fixtureIdentifier',
  25: 'keywords',
  26: 'contentLocationCode',
  27: 'contentLocationName',
  30: 'releaseDate',
  35: 'releaseTime',
  37: 'expirationDate',
  38: 'expirationTime',
  40: 'specialInstructions',
  42: 'actionAdvised',
  45: 'referenceService',
  47: 'referenceDate',
  50: 'referenceNumber',
  55: 'dateCreated',
  60: 'timeCreated',
  62: 'digitalCreationDate',
  63: 'digitalCreationTime',
  65: 'originatingProgram',
  70: 'programVersion',
  75: 'objectCycle',
  80: 'byLine',
  85: 'byLineTitle',
  90: 'city',
  92: 'subLocation',
  95: 'province_state',
  100: 'country_primaryLocationCode',
  101: 'country_primaryLocationName',
  103: 'originalTransmissionReference',
  105: 'headline',
  110: 'credit',
  115: 'source',
  116: 'copyrightNotice',
  118: 'contact',
  120: 'caption_abstract',
  122: 'writer_editor',
  #125: 'rasterizedCaption', # unsupported (binary data)
  130: 'imageType',
  131: 'imageOrientation',
  135: 'languageIdentifier',
  200: 'custom1', # These are NOT STANDARD, but are used by
  201: 'custom2', # Fotostation. Use at your own risk. They're
  202: 'custom3', # here in case you need to store some special
  203: 'custom4', # stuff, but note that other programs won't
  204: 'custom5', # recognize them and may blow them away if
  205: 'custom6', # you open and re-save the file. (Except with
  206: 'custom7', # Fotostation, of course.)
  207: 'custom8',
  208: 'custom9',
  209: 'custom10',
  210: 'custom11',
  211: 'custom12',
  212: 'custom13',
  213: 'custom14',
  214: 'custom15',
  215: 'custom16',
  216: 'custom17',
  217: 'custom18',
  218: 'custom19',
  219: 'custom20',
}
txt_datasets = dict([(v, k) for k,v in nr_datasets.items()])




##IPTC:
## http://www.iptc.org/IIM/
## http://www.controlledvocabulary.com/imagedatabases/iptc_naa.html
##
## also see Exiv2 C++ app: http://home.arcor.de/ahuggel/exiv2/iptc.html
## Const Byte Jpegbase::App13_  = 0Xed;        - iptc here
## Const Uint16_T Jpegbase::Iptc_ = 0X0404;
##
## iptc.cpp ::READ (LINE 152)
## dataset marker 1 byte   0x1C
## record         1 byte
## dataset        1 byte
##
## if next byte is 0x08 then this is extended dataset:
##     pass
## otherwise is standard:
##     dataset len    short    (big endian)