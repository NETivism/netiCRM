#!/bin/bash
GITTAG=`git describe --tags`
IFS='-' read -ra TAG <<< "$GITTAG"
GITREVISION=`git rev-parse --short HEAD`
GITTAG="${TAG[0]}+${TAG[1]} (${GITREVISION})"
echo $GITTAG
