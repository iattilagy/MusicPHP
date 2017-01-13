#!/usr/bin/python

from __future__ import print_function
import subprocess
from os import listdir
import mysql.connector
from os.path import isdir
from os.path import splitext
import sys
reload(sys)  # Reload does the trick!
sys.setdefaultencoding('UTF8')

def getVorbisTag(filename,tagname):
    filename=filename.replace("'",r"\'");
    proc = subprocess.Popen(["metaflac --list '" + filename + "'|grep --ignore-case ': "+tagname+"'"], stdout=subprocess.PIPE, shell=True)
    (out, err) = proc.communicate()
    try:
        out = out[out.index("=")+1:]
    except:
        return ""
    out=out.replace("'","''")
    return out[:-1]

def getSampleRate(filename):
    proc = subprocess.Popen(["metaflac --list '" + filename + "'|grep --ignore-case 'sample_rate:'"], stdout=subprocess.PIPE, shell=True)
    (out, err) = proc.communicate()    
    try:
        out = out[out.index(":")+1:]
    except:
        return ""
    return out[:-1]

def getBit(filename):
    proc = subprocess.Popen(["metaflac --list '" + filename + "'|grep --ignore-case 'bits-per-sample:'"], stdout=subprocess.PIPE, shell=True)
    (out, err) = proc.communicate()
    try:
        out = out[out.index(":")+1:]
    except:
        return ""
    return out[:-1]+"bit"

def getArtistId(name, cursor):
    cursor.execute("Select id from artist where name='"+name+"';")
    result=cursor.fetchone()
    if result==None:
        cursor.execute("insert into artist(name) values ('"+name+"');")
        cnx.commit()
    else:
        return result[0]
    cursor.execute("Select id from artist where name='"+name+"';")
    return cursor.fetchone()[0]

def getAlbumId(filename,title, year, genre, cursor):
    cursor.execute("Select id from albums where album_title='"+title+"';")
    result=cursor.fetchone()
    if result==None:
        cursor.execute("insert into albums(album_title,year,genre) values ('"+title+"','"+year+"','"+genre+"');")
        cnx.commit()
        proc = subprocess.Popen(["metaflac --export-picture-to='/srv/www/htdocs/albumCovers/"+title.replace(" ","")+".jpg' '"+filename+"'"], stdout=subprocess.PIPE, shell=True)
        (out, err) = proc.communicate()
        print (out)
    else:
        return result[0]
    cursor.execute("Select id from albums where album_title='"+title+"';")
    return cursor.fetchone()[0]

def addTrack(filename,title,tracknumber,sampling,bitrate,album_id,artist_id):
    cursor.execute("insert into tracks(filename,title,tracknumber,sampling,bitrate,album_id,artist_id) values ('"+filename+"','"+title+"','"+tracknumber+"','"+sampling+"','"+bitrate+"',"+album_id+","+artist_id+");")
    cnx.commit()
    cursor.execute("select id from tracks where title='"+title+"' and album_id='"+album_id+"';")    
    return cursor.fetchone()[0]

cnx = mysql.connector.connect(user='music', password='muse',
                              host='127.0.0.1',
                              database='music')

DELETE_TRACKS_TABLE='Drop table if exists tracks'
CREATE_TRACKS_TABLE=("CREATE TABLE tracks ("
                    " id int NOT NULL AUTO_INCREMENT,"
                    " filename VARCHAR(512),"
                    " title VARCHAR(256) NOT NULL,"
                    " tracknumber VARCHAR(16),"
                    " sampling VARCHAR(32),"
                    " bitrate VARCHAR(32),"
                    " album_id int references albums(id),"
                    " artist_id int references artist(id),"
                    " PRIMARY KEY(id));")
CREATE_ALBUM_TABLE=("Create table if not exists albums ("
                   " id int NOT NULL AUTO_INCREMENT,"
                   " album_title VARCHAR(256),"
                   " year VARCHAR(64),"
                   " genre VARCHAR(32),"
                   " PRIMARY KEY(id));")

CREATE_ARTIST_TABLE=("Create table if not exists artist ("
                    " id int NOT NULL AUTO_INCREMENT,"
                    " name VARCHAR(128),"
                    " PRIMARY KEY(id));")

CREATE_SETTINGS_TABLE=("Create table if not exists settings ("
                    " id int NOT NULL AUTO_INCREMENT,"
                    " name VARCHAR(128) NOT NULL,"
                    " value VARCHAR(64),"
                    " PRIMARY KEY(id));")

CREATE_PLAYLISTS_TABLE=("Create table if not exists playlists ("
                    " id int NOT NULL AUTO_INCREMENT,"
                    " name VARCHAR(256),"
                    " PRIMARY KEY(id));")

CREATE_TRACK_PLAYLIST_TABLE=("Create table if not exists playlistlinker("
                    " playlist_id int references playlists(id),"
                    " track_id int references tracks(id),"
                    " number int NOT NULL,"
                    " id int NOT NULL AUTO_INCREMENT,"
                    " PRIMARY KEY(id));")
cursor=cnx.cursor()

try:
    cursor.execute(DELETE_TRACKS_TABLE)
except mysql.connector.Error as err:
    print(err.msg)
else:
    print("delete tracks OK\n")
    
try:
    cursor.execute(CREATE_TRACKS_TABLE)
except mysql.connector.Error as err:
    print(err.msg)
else:
    print("create tracks OK\n")
    
try:
    cursor.execute(CREATE_ALBUM_TABLE)
except mysql.connector.Error as err:
    print(err.msg)
else:
    print("create albums OK\n")
try:
    cursor.execute(CREATE_ARTIST_TABLE)
except mysql.connector.Error as err:
    print(err.msg)
else:
    print("create albums OK\n")

try:
    cursor.execute(CREATE_SETTINGS_TABLE)
except mysql.connector.Error as err:
    print(err.msg)
else:
    print("create settings OK\n")
    
try:
    cursor.execute(CREATE_PLAYLISTS_TABLE)
except mysql.connector.Error as err:
    print(err.msg)
else:
    print("create playlist OK\n")
    
try:
    cursor.execute(CREATE_TRACK_PLAYLIST_TABLE)
except mysql.connector.Error as err:
    print(err.msg)
else:
    print("create track-playlist OK\n")
   
#Create playi for storing current played id
cursor.execute("insert into settings (name) values('playi');")

#Playing defines if music is stopped or playing
cursor.execute("insert into settings (name) values('playing');")

#currently playing album id
cursor.execute("insert into settings (name, value) values('album','0');")

#currently playing playlist id
cursor.execute("insert into settings (name, value) values('playlist','0');")

for dir in listdir("."):
    if isdir(dir):
        for file in listdir("./"+dir):
            file=dir+"/"+file
            if splitext(file)[1]==".flac":
                artistid=getArtistId(getVorbisTag(file,"artist"),cursor)
                albumid=getAlbumId(file,getVorbisTag(file,"album="), \
                    getVorbisTag(file,"date"), getVorbisTag(file,"genre"), cursor)
                trackid=addTrack(file,getVorbisTag(file,"title"),\
                    getVorbisTag(file,"tracknumber"),getSampleRate(file),\
                        getBit(file),str(albumid),str(artistid))
                print (str(trackid)+" "+getVorbisTag(file,"title")\
                            +" "+getVorbisTag(file,"tracknumber"))

cnx.close()
