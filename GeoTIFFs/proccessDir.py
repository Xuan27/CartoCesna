import os, subprocess

for f in os.listdir('.'):
    if os.path.isfile(f):
		extension =  f[-3:]
		if extension == "tif":
			subprocess.call(["python", "addGeometry2DB.py", f])