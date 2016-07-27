#-------------------------------------------------------------------------------
# Name:        module1
# Purpose:
#
# Author:      jmartinez91
#
# Created:     25/07/2016
# Copyright:   (c) jmartinez91 2016
# Licence:     <your licence>
#-------------------------------------------------------------------------------
import json
from osgeo import gdal, osr
import subprocess
import mysql.connector
import os
import fnmatch

for dirpath, dirnames, filenames in os.walk("C:\wamp\www\CartoCesna\GeoTIFFs", ".tif*"):
    for filenames in fnmatch.filter(filenames,'*.tif'):
       Name = [os.path.join(dirpath, filenames)]
       for element in Name:
        print element
        command = ("gdalinfo -json %s" % (element))
        output = subprocess.check_output(command)

        data = json.loads(output)

        Centroid = data["cornerCoordinates"]["center"]
        Filename= filenames


        db= mysql.connector.connect(host = "localhost", user = "root", passwd = "", db = "spatial_query")
        cursor = db.cursor()

        add_json = ("""INSERT INTO geom2 (Filename, g) VALUES (%s, ST_GeomFromText('POINT(%s %s)'))""")



        Coordinates = Centroid
        Centx = Coordinates[0]
        Centy = Coordinates[1]
        info= (Filename, Centx, Centy)
        print info

        cursor.execute(add_json, info)
        db.commit()

db.close()
