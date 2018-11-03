#!/usr/bin/python

#Require lib
#https://github.com/adafruit/Adafruit_Python_DHT
import sys
import json
import Adafruit_DHT
import os
os.environ['PYTHON_EGG_CACHE'] = '/var/www/.python-eggs'

sensor = Adafruit_DHT.AM2302
pin = 21 

# Try to grab a sensor reading.  Use the read_retry method which will retry up
# to 15 times to get a sensor reading (waiting 2 seconds between each retry).
humidity, temperature = Adafruit_DHT.read_retry(sensor, pin)

if humidity is not None and temperature is not None and humidity <= 100 and humidity > 0:
    print ('Content-Type: application/json\n\n');
    print json.dumps({'temp':temperature,'humidity':humidity})
    sys.exit(1)
else:
    print('Failed to get reading. Try again!')
    sys.exit(0)


