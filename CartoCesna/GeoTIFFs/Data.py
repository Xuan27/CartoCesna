#-------------------------------------------------------------------------------
# Name:        module1
# Purpose:
#
# Author:      jmartinez91
#
# Created:     27/07/2016
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
        Polygon_upper_Right_X = data["cornerCoordinates"]["upperRight"][0]
        Polygon_upper_Right_Y = data["cornerCoordinates"]["upperRight"][1]

        Polygon_lowerLeft_X = data["cornerCoordinates"]["lowerLeft"][0]
        Polygon_lowerLeft_Y = data["cornerCoordinates"]["lowerLeft"][1]

        Polygon_lower_Right_X = data["cornerCoordinates"]["lowerRight"][0]
        Polygon_lower_Right_Y = data["cornerCoordinates"]["lowerRight"][1]

        Polygon_upper_Left_X = data["cornerCoordinates"]["upperLeft"][0]
        Polygon_upper_Left_Y = data["cornerCoordinates"]["upperLeft"][1]
        #print Polygon_upper_Right_X, Polygon_upper_Right_Y, Polygon_lowerLeft_X, Polygon_lowerLeft_Y, Polygon_lower_Right_X, Polygon_lower_Right_Y, Polygon_upper_Left_X, Polygon_upper_Left_Y
        Filename= filenames

       db= mysql.connector.connect(host = "localhost", user = "root", passwd = "", db = "spatial_query")
       cursor = db.cursor()

       add_json = ("""INSERT INTO geom2 (Filename, g, Poly) VALUES (%s, ST_GeomFromText('POINT(%s %s)'), ST_GeomFromText('POLYGON((%s %s,%s %s,%s %s,%s %s, %s %s))'))""")

#INSERT INTO geom2 (Filename, g, Poly) VALUES (%s, ST_GeomFromText('POINT(%s %s)'), GeomFromText('POLYGON((%s %s,%s %s,%s %s,%s %s))'))

    Coordinates = Centroid
    Centx = Coordinates[0]
    Centy = Coordinates[1]
    info= (Filename, Centx, Centy, Polygon_upper_Left_X, Polygon_upper_Left_Y, Polygon_upper_Right_X, Polygon_upper_Right_Y, Polygon_lower_Right_X, Polygon_lower_Right_Y, Polygon_lowerLeft_X, Polygon_lowerLeft_Y, Polygon_upper_Left_X, Polygon_upper_Left_Y)
    print info

    cursor.execute(add_json, info)
    db.commit()

db.close()
