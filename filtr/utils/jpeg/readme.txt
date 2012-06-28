What is it
----------

    - The module is part of my web *personal media cataloging* application WCatalog_
    - **Read/ Write JPEG Exif segment** type metadata

      This program will never try to achieve completeness, for example photo camera maker notes altow parsed for some comera's
      (olympus, canon) will probably never really be fully parsed. The way the program parses them is in "raw" format so that it
      can write them back in to the file corectly, meaning that it creates a class with tags and their values but there is no mapping 
      to the acual meaning of the tags (attribute parsedMakerNotes ), except for a few camera's I actualy need that info myself and 
      I found usefull to do it  (I would very much welcome any contributions) like my canon and olympus cameras (attribute makerNotes).
      
      Attention! there are cameras that use a proprietary (not exif like) method of structuring the maker notes (like kodak camera's) 
      and in their case there are very big chances that the maker notes will be corupted after this program writes a new exif in to them.
      
      For more exhaustive maping of maker notes you should use EXIF.py or pyExif_ or if python is not a neccesity for you 
      then there is a very good perl module exiftool_.
      
      However neither of the python modules can write data back into the image and as far as I know there is not
      a lot of programs to chose from that are open source and can do it.
      There are a few like the perl exiftool or exiv2_, a C++ library for Exif and IPTC, which looks like a mature library. I'm looking forward 
      for someone to port it to python.

    - **Read/ Write JPEG** `IPTC segment`_ **type** metadata_ **(new in jpeg 0.1.4)** 
    
      The iptc module is yet in an alpha state, you should backup your file before using it.
      
      Some of it's interface may change in the future.
      
    - **Read/write data in the Comments segment** of the JPEG.
    - Thumbnail via .thumbnail attribute
    
    - The user is expected to do some homework if he tries to use the module to
      write metadata outside of it's existing explicit interface.

      By this I mean that if the user tries to use the generic methods *get/set*, he will need to know about Exif 
      tag id's and their type (see www.exif.org_ for specifications)


Download & Install
------------------

     - download `the modules`_ zip file, decompres and run *python setup.py install*
     - license_


How it works
------------

    - Simple JPEG Comments
      
      .. code-block:: Python

            import jpeg

            #read/write JPEG comments (aka the COM area)
            jpeg.getComments(file)
            jpeg.setComments(txt, file)

    
    - EXIF style metadata  
      
      .. code-block:: Python
      
            e = jpeg.getExif(file)   #an Exif object (see Exif class documentation for details)
            e2 = jpeg.getExif2(file) #an Exif extension segment as Exif instance too
            e3 = jpeg.getExif("http://www.emilas.com/mylogo.jpg")

            #quick getter
            d = e.dict()
            print d['image description']
            e.display()  #function to print all tags and their value, type, etc.

            #some tags with an explicit interface, some are writable
            print e.description
            e.description = "my nice picture"

            #generic read function
            print e.get(0x010e)  #value for a tag number you know
            print e.get(0x010e, value=False) #get the tag as a Tag instance

            #generic write function
            e.set(0x010e, "my nice picture", e.ifd0, 2)
                #e.ifd0, e.exif, e.gps or e.interop, represents the IFD the tag belongs to
                #and 2 represents the fact that the tag is of ASCII type
                #see www.exif.org specifications for details

            #iterate
            for tag in e:
               print tag.getValue() #value parsed
               print tag.value      #value raw
               tag.setValue(100)    #change it

            e.makerNotes  #this may be None
            e.makerNotes.ISO  #for a canon camera for example
            
            e.rawMakerNotes #not parsed at all
            e.parsedMakerNotes #an object resemmbling an exif object with tags and values
            
            
            #save the changed Exif segment back into the image file or other file for that matter
            #even one that does not have an exif segment
            jpeg.setExif(e, file)
            
            stream = jpeg.setExif(exif, "http://emilas.com/other/mylogo2.jpg")
            #stream is now a file like object with the exif contend of mylogo2.jpg
            #modified as in mylogo.jpg

            
    - IPTC style metadata  (aka the APP13 area)
      
      .. code-block:: Python          
          
            #read/write IPTC metadata 
            iptc = jpeg.getIPTC(file)
            
            print iptc.supported_iptc_attributes
            print display()  

            print iptc.caption_abstract
            iptc.caption_abstract.value = "a new descrtiption"
            
            iptc.create("keywords", "jpeg metadata")
            iptc.create("keywords", "iptc read/write")
            print iptc.keywords[0].value

            #iterate
            for tag in iptc:
               print tag    #some of the tags are lists, like the keywords above
               tag.value = "set it"
            
            jpeg.setIPTC(iptc, file)

.. _WCatalog: ../wcatalog/
.. _pyExif: http://pyexif.sourceforge.net/
.. _exiv2: http://home.arcor.de/ahugge/exiv2/
.. _www.exif.org : http://www.exif.org/
.. _`the modules`: download/
.. _license: COPYRIGHT.txt
.. _`IPTC segment`: http://www.iptc.org/IIM/
.. _metadata: http://www.controlledvocabulary.com/imagedatabases/iptc_naa.html
.. _exiftool: http://www.sno.phy.queensu.ca/~phil/exiftool/
