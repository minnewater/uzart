#!/bin/bash

orgFile=/data/ftp/api/wellc/uzart/uzart.sh

sed s/API_KEY=\"\"/API_KEY=\"$1\"/g $orgFile > $2
gzexe $2
