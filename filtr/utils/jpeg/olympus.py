from exif import InterpretedMakerNotes

olympus_map = {
    0x204: { 'DigitalZoom': {},
		},
	0x201: { 'JPEGQuality': { 
				1:"SQ",
				2:"HQ",
				3:"SHQ"
            }
        },
	0x202: { 'Macro': {
				0:"Normal",
				1:"Macro"
	        }
        }
    }


class MakerNotes(InterpretedMakerNotes):
    def __init__(self, exif):
        super(MakerNotes, self).__init__(exif)

        for tag in olympus_map.keys():
            t = self._raw.get(tag)
            el, val = olympus_map[tag].items()[0]
            if val=={}:
                val = t.value
            else:
                val = val.get(t.value, "uknown: %s" % t.value)
            setattr(self.__class__, el, property(fget = lambda s, v=val: v))   #make it read only

