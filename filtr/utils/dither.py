#!/usr/bin/env python

has_atk = False

import sys

try:
    import atk
    has_atk = True
except Exception, e:
    pass

try:
    import Image
except Exception, e:
    import PIL.Image as Image


def dither(infile, outfile):

    img = Image.open(infile)

    if has_atk:
        img = dither_atk(img)
    else:
        img = dither_python(img)

    img.save(outfile)

def dither_atk(img):

    img = img.convert('L')
    tmp = atk.atk(img.size[0], img.size[1], img.tostring())
    new = Image.fromstring('L', img.size, tmp)

    return new.convert('RGBA')

def dither_python(infile, outfile):

    img = img.convert('L')

    threshold = 128*[0] + 128*[255]

    for y in range(img.size[1]):
        for x in range(img.size[0]):

            old = img.getpixel((x, y))
            new = threshold[old]
            err = (old - new) >> 3 # divide by 8
            
            img.putpixel((x, y), new)
        
            for nxy in [(x+1, y), (x+2, y), (x-1, y+1), (x, y+1), (x+1, y+1), (x, y+2)]:
                try:
                    img.putpixel(nxy, img.getpixel(nxy) + err)
                except IndexError:
                    pass

    return img.convert('RGBA')

if __name__ == '__main__':

    infile = sys.argv[1]
    outfile = sys.argv[2]
    
    dither(infile, outfile)
