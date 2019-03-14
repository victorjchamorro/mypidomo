#!/usr/bin/python

 #
 # @author Victor J. Chamorro <victorjchamorro@gmail.com>
 #
 # LGPL v3 - GNU LESSER GENERAL PUBLIC LICENSE
 #
 # This program is free software: you can redistribute it and/or modify
 # it under the terms of the GNU LESSER General Public License as published by
 # the Free Software Foundation, either version 3 of the License.
 # 
 # This program is distributed in the hope that it will be useful,
 # but WITHOUT ANY WARRANTY; without even the implied warranty of
 # MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 # GNU General Public License for more details.
 #
 # You should have received a copy of the GNU LESSER General Public License
 # along with this program.  If not, see <http://www.gnu.org/licenses/>.
 #

#Require lib
#https://github.com/adafruit/Adafruit_Python_DHT
import sys
import json
import Adafruit_DHT
import os
import sqlite3
import time

os.environ['PYTHON_EGG_CACHE'] = '/var/www/.python-eggs'

conn = None

def sqliteConnect():
	global conn
	conn =sqlite3.connect('./data/mypidomo.db')

def sqliteClose():
	global conn
	conn.close()

def initDatabase():
	global conn
	c = conn.cursor();
	c.execute("""CREATE TABLE IF NOT EXISTS temp(
			date TEXT NOT NULL PRIMARY KEY,
			temperature REAL NOT NULL,
			humidity REAL NOT NULL,
			pressure REAL NOT NULL
			)""")
	#c.execute("DELETE FROM temp");
	conn.commit()
	
#initDatabase();

sensor = Adafruit_DHT.AM2302
pin = 21 

# Try to grab a sensor reading.  Use the read_retry method which will retry up
# to 15 times to get a sensor reading (waiting 2 seconds between each retry).

on=1
while(on):

	#sqliteConnect()
	
	humidity, temperature = Adafruit_DHT.read_retry(sensor, pin)

	if humidity is not None and temperature is not None and humidity <= 100 and humidity > 0:
		#c = conn.cursor()
		#c.execute('INSERT INTO temp VALUES(datetime("now"),'+str(temperature)+','+str(humidity)+',0)');
		#conn.commit()
		print json.dumps({'temp':temperature,'humidity':humidity})
	else:
		print('Failed to get reading. Try again!')
	
	#sqliteClose()
	on=0
	#time.sleep(60)


