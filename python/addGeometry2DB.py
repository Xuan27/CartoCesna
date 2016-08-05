import json
from osgeo import gdal 
import os
import subprocess
import MySQLdb
import sys

fileName = sys.argv[1]
kmz = fileName[:-3]
kmz = "../kmzFiles/" + kmz + "kmz"

print kmz
  
command = "gdalinfo -json {0} " .format(fileName)
output = subprocess.check_output(command)
print command

data = json.loads(output)
json = data['cornerCoordinates']

lat =  data['cornerCoordinates']['center'][0]
lng = data['cornerCoordinates']['center'][1]
x1 = data["cornerCoordinates"]["upperRight"][0]
y1 = data["cornerCoordinates"]["upperRight"][1]

x2 = data["cornerCoordinates"]["lowerLeft"][0]
y2 = data["cornerCoordinates"]["lowerLeft"][1]

#print center 
print data['cornerCoordinates']['center']

command0= "SET @centroid = 'POINT({0} {1})'".format(lat, lng) 
command1 = """INSERT INTO table1 VALUES ('{0}', GeomFromText(@centroid), '{1}', '{2}', '{3}', '{4}', '{5}') """.format(fileName, x1, y1, x2, y2, kmz)

db = MySQLdb.connect("localhost", "neilgibeaut", "Sadiedog1995", "spatialSearch_db")

c = db.cursor()

c.execute(command0)
c.execute(command1)
db.commit()

c.close()
db.close()