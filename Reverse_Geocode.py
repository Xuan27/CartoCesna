#-------------------------------------------------------------------------------
# Name:        Reverse_Geocode
# Purpose:     Provide X and Y coordinates and a street adress will be returned
#
# Author:      Juan Martinez
#
# Created:     10/06/2016
# Copyright:   (c) Juan 2016
#-------------------------------------------------------------------------------

import arcpy
Workspace =r"C:\Users\Juan\Documents\GIS Projects\CBCOG\data\Shreveport.gdb"
arcpy.env.workspace = Workspace
arcpy.env.overwriteOutput = True

Reverse_Geocode = "Reverse_Geocode"
Spatial_Reference = arcpy.SpatialReference(26915)
Street_Point = "Street_Point"
Point_in_the_line = "Point_in_the_line"
X_Y_Coordinates = ["SHAPE@X", "SHAPE@Y"]
X_Y_Near = ["NEAR_X", "NEAR_Y"]
X_Y_Dist_Angle = ["NEAR_X", "NEAR_Y", "NEAR_DIST", "NEAR_ANGLE"]

#Reverse Geocode shapefile is created  with the x and y coordinates given as parameters
arcpy.CreateFeatureclass_management(Workspace, Reverse_Geocode, "POINT", spatial_reference = Spatial_Reference)

with arcpy.da.InsertCursor(Reverse_Geocode, X_Y_Coordinates) as cs:
    cs.insertRow((-93.863392, 32.481861))

#The x and y coordinates of the closest street are obtained with the NEAR ANALYSIS TOOL
Features = arcpy.ListFeatureClasses()
for feature in Features:
    if feature == "Streets":
        Street = feature
    elif feature == Reverse_Geocode:
        Reverse_Geocode_Point = feature

Near_Street = arcpy.Near_analysis(Reverse_Geocode_Point, Street, location = True, angle = True, method = "GEODESIC")

#Using the Near_FID field from the Reverse geocoded point a street is selected
with arcpy.da.SearchCursor(Reverse_Geocode_Point, "NEAR_FID") as Cs:
    for row in Cs:
        whereClause = """"OBJECTID" = {0}""".format(row[0])
Street_Layer = "Street_Layer"
arcpy.MakeFeatureLayer_management(Street, Street_Layer)
arcpy.SelectLayerByAttribute_management(Street_Layer, where_clause = whereClause)

#Creates the selected street shapefile
#arcpy.CopyFeatures_management(Street_Layer, "Selected_Street")
Selected_Street_X_Y = arcpy.Array()
with arcpy.da.SearchCursor(Street_Layer, X_Y_Coordinates, explode_to_points = True) as cs:
    for row in cs:
        Selected_Street_X_Y.append(arcpy.Point(row[0],row[1]))

Selected_Street_Geometry = arcpy.Polyline(Selected_Street_X_Y, Spatial_Reference)
arcpy.CopyFeatures_management(Selected_Street_Geometry, "Selected_Street_Geometry")
Selected_Street_Geometry_Length = Selected_Street_Geometry.length
#Selected_Street_Geometry_Length = int(Selected_Street_Geometry_Length % 25)
print Selected_Street_Geometry_Length

#A POINT GEOMETRY is created using the coordinates from the near analysis
with arcpy.da.SearchCursor(Reverse_Geocode, X_Y_Near) as cs:
    for row in cs:
        Search_X = row[0]
        Search_Y = row[1]
        Search_Point = arcpy.Point(row[0], row[1])
        Street_Point_Geometry = arcpy.PointGeometry(Search_Point)

#A STREET POINT SHAPEFILE is created using the coordinates from the point geometry and SNAPPED to the street
arcpy.CreateFeatureclass_management(Workspace, Street_Point, "POINT", spatial_reference = Spatial_Reference)
with arcpy.da.InsertCursor(Street_Point, X_Y_Coordinates) as cs:
    cs.insertRow((Search_X, Search_Y))
snapEnv1 = [Street, "EDGE", "25 Feet"]
arcpy.Snap_edit(Street_Point, [snapEnv1])
arcpy.Near_analysis(Street_Point, Street, location = True, angle = True, method = "GEODESIC")

