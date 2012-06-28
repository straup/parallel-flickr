import struct
import sys

reverse = lambda txt: txt[::-1]

def unzip(seq, nr):
    """ - unzip([1,2,3,4,5,6,7,8,9,10], 3) -> [(1,2,3),(4,5,6),(7,8,9), (10,)]
        - unzip("1234567890", 3)-> [(1,2,3),(4,5,6),(7,8,9), (0,)]"""
    lst=[]; mainlst=[]
    def do(itm):
        if len(lst)<nr: lst.append(itm)
        else:
            mainlst.append(tuple(lst))
            lst[:]=[]; lst.append(itm)
    map(do,seq)
    if lst: mainlst.append(tuple(lst))
    return mainlst

#"MM" big endian (Motorola, Mac, Sun), left to right
#"II" little endian (Intel, Windows, Linux), right to left
#JPEG is Big Endian and EXIF may be either one, my Olimpus camera writes EXIF in little endian 
#and many other cameras seem to do that too
if sys.byteorder == "big":
    ENDIAN = "MM"
else:
    ENDIAN = "II"

def endian_padd(val, with_this=0):
    if ENDIAN == "II":
        return val + with_this
    return with_this + val

# should the use of struct be refactored using bit calculation ?
# TODO: add support for signed data
def getNr(nrStr, endian="MM"):
    "given a binary representation of a number in <endian> big or little, return a python int"
    #by default jpeg is MM but we are converting it to ENDIAN and we return an int
    ln = len(nrStr)
    frm = ln > 4 and ('q', 8) or ('i', 4)
    if endian != ENDIAN:  #convert to Intel and padd with NULLs to get <type>
        val = endian_padd(reverse(nrStr), '\x00' * (frm[1]-ln))
    else: 
        val = endian_padd(nrStr, '\x00' * (frm[1]-ln))  
    return struct.unpack(frm[0], val)[0]

def setNr(nr, type="int", endian="MM"): 
    "return binary representation of given number acording to <endian> big or little"
    tp = {"long":'q', 8:'q', "int":'i', 4:'i', "short":'h', 2:'h', 'byte':'B', 1: 'B'}
    frm = tp.get(type, 'i') #default int
    val = struct.pack(frm, nr)
    if endian == ENDIAN: 
        return val
    else: 
        return reverse(val)
    
