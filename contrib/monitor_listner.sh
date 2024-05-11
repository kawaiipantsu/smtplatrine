#!/bin/sh

## This script is just very simple script to monitor the port listener and establish connection(s)
## It's just to make it easy to see what's going on when testing/debugging the server
##
## RUN: watch ./monitor_listner.sh

# Get port from server.ini
PORT=`cat ../etc/server.ini | grep "port" | awk '{print $3}' | tr -d '[:space:]'`

echo "Monitoring for port: $PORT"
echo "--------------------------------------------"
echo " "
#lsof -i -P -n | grep "1025"
lsof -i -P -n 2>&1 | grep ":$PORT " | awk '{print "[ "$1" ] -> "$5"/"$8" "$9" "$10}'

