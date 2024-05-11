#!/bin/sh
git-graph --no-pager -n 80 --color always --format="%p:%h %d '%as' >> '%an' | %s" --path ../
