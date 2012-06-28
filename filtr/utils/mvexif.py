#!/usr/bin/env python

# http://www.emilas.com/jpeg/

from jpeg import jpeg
import sys

path_input = sys.argv[1]
path_output = sys.argv[2]

try :
    exif = jpeg.getExif(path_input)
except Exception, e:
    # please to write to STDERR here...
    print "get exif: %s (%s)" % (e, path_input)
    sys.exit()
    
if exif != None :

    try :
        jpeg.setExif(exif, path_output)
    except Exception, e :        # please to write to STDERR here...        
        print "set exif: %s (%s)" % (e, path_output)
        sys.exit()
    
sys.exit()
