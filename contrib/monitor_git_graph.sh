#!/bin/sh
./git-graph --no-pager -n 80 --format="%p:%h %d '%as' >> '%an' | %s" --path ../
