#!/bin/sh

## This script is just very simple script to monitor the git changes in a fancy way.
## Requires git-graph to be installed.
##
## RUN: watch -wc ./monitor_listner.sh

git-graph --no-pager -n 80 --color always --format="%p:%h %d '%as' >> '%an' | %s" --path ../
