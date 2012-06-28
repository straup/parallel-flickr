##Forums:
##  http://www.photohelp.info/cameras/canon/thread19.html
##  http://72.14.203.104/search?q=cache:YYpAe4uzONgJ:gallery.menalto.com/node/55248+canon+EXIF+ISO&hl=en&gl=us&ct=clnk&cd=9&client=firefox-a
##
##Additionally and most importantly I have discovered that using the Windows XP publish facility with G2 screws up the EXIF Makernotes.
##ISO and other settings become unreadable and they are not displayed by the EXIF module at all. This is really importnat for Canon cameras
##because of the way Makernotes are used so extensively.
##If you use the browser upload option all the EXIF incl. Makernotes remain intact.
            
from exif import InterpretedMakerNotes

canon_map = {
    0x01: { 'Bytes': {'mindex':0},                     
			'Macro': { 'mindex':1,
				1:"Macro",
				2:"Normal"},
			'SelfTimer': {'mindex':2,                    #2
				0:"Off"},
			'Quality': {  'mindex':3,                 #3
				2:"Normal",
				3:"Fine",
				5:"Superfine"},
			'Flash': { 'mindex':4,                  #4
				0:"Off",
				1:"Auto",
				2:"On",
				3:"Red Eye Reduction",
				4:"Slow Synchro",
				5:"Auto + Red Eye Reduction",
				6:"On + Red Eye Reduction",
				16:"External Flash",
				#Canon Powershot S3 IS:
				80:"No Flash",
                89:"Fired - Red Eye, Auto-Mode",
                93:"Fired - Red Eye, Auto-Mode, Return light not detected",
                95:"Fired - Red Eye, Auto-Mode, Return light detected"
				},
			'DriveMode': { 'mindex':5,                  #5
				0:"Single/Timer",
				1:"Continuous"},
			'FocusMode': { 'mindex':7,                  #7
				0:"One-Shot",
				1:"AI Servo",
				2:"AI Focus",
				3:"Manual Focus",
				4:"Single",
				5:"Continuous",
				6:"Manual Focus"},
			'ImageSize': { 'mindex':10,                  #10
				0:"Large",
				1:"Medium",
				2:"Small"},
			'EasyShooting': { 'mindex':11,                  #11
				0:"Full Auto",
				1:"Manual",
				2:"Landscape",
				3:"Fast Shutter",
				4:"Slow Shutter",
				5:"Night",
				6:"Black & White",
				7:"Sepia",
				8:"Portrait",
				9:"Sport",
				10:"Macro/Close-Up",
				11:"Pan Focus"},
			'DigitalZoom': { 'mindex':12,                  #12
				0:"No Digital Zoom",
        		1:"2x",
        		2:"4x",
				65535:"No Digital Zoom"},
			'Contrast': { 'mindex':13,                  #13
				0:"Normal",
				1:"High",
				65535:"Low"},
			'Saturation': { 'mindex':14,                  #14
				0:"Normal",
				1:"High",
				65535:"Low"},
			'Sharpness': { 'mindex':15,                  #15
				0:"Normal",
				1:"High",
				65535:"Low"},
			'ISO': {  'mindex':16,                 #16
				15:"Auto",
				16:"50",
				17:"100",
				18:"200",
				19:"400",
				#Canon Powershot S3 IS:
                14: "Hi",
                16464: "80",
                16484: "100",
                16584: "200",
                16784: "400",
                17184: "800",
                "Auto": "Auto"
				},
			'MeteringMode': {  'mindex':17,                 #17
				3:"Evaluative",
				4:"Partial",
				5:"Center-weighted"},
			'FocusType': {  'mindex':18,                 #18
				0:"Manual",
				1:"Auto",
				3:"Close-up (Macro)",
				8:"Locked (Pan Mode)"},
			'AFPointSelected': { 'mindex':19,                  #19
				12288:"Manual Focus",
				12289:"Auto Selected",
				12290:"Right",
				12291:"Center",
				12292:"Left"},
			'ExposureMode': {  'mindex':20,                 #20
				0:"EasyShoot",
				1:"Program",
				2:"Tv",
				3:"Av",
				4:"Manual",
				5:"Auto-DEP"},
			'LongFocalLength': {'mindex':23},                     #23
			'ShortFocalLength': {'mindex':24},                     #24
			'FocalUnits': {'mindex':25},                   #25
			'FlashActivity': { 'mindex':28,                  #28
				0:"Flash Did Not Fire",
				1:"Flash Fired"},
			'FlashDetails': {'mindex':29},                 #29
			'FocusMode': {'mindex':32}                  #32
		},
	0x04: { 'Bytes': {'mindex':0},                     #0
			'WhiteBalance': { 'mindex':7,                  #7
				0:"Auto",
				1:"Sunny",
				2:"Cloudy",
				3:"Tungsten",
				4:"Flourescent",
				5:"Flash",
				6:"Custom"},
			'SequenceNumber': {'mindex':9},                     #9
			'AFPointUsed': {'mindex':14},                     #14
			'FlashBias': { 'mindex':15,                  #15
				'.':"EV"},
			'SubjectDistance': {'mindex':19},                     #19
        }
        }

class MakerNotes(InterpretedMakerNotes):
    def __init__(self, exif):
        super(MakerNotes, self).__init__(exif)

        for tag in canon_map.keys():
            t = self._raw.get(tag)
            for el, val in canon_map[tag].items():
                if len(val.keys())==1:
                    val = t.value[val['mindex']]
                else:
                    val = val.get(t.value[val['mindex']], "uknown: %s" % t.value[val['mindex']])
                setattr(self.__class__, el, property(fget = lambda s, v=val: v))   #make it read only

