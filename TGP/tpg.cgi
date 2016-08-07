#!/bin/bash

#
# YES, THIS IS A WEB SERVER WRITTEN IN BASH
#
# URL REMOVED, I KNOW THIS WEBSITE IS STILL ACTIVE
#

echo "Content-type: text/html"
echo

export LYNX_TEMP_SPACE="/tmp"
cd tmp

echo "<html><head><style>a:visited{visibility:hidden;font-size:0px}</style></head><body>"
lynx --source "http://XXXXX/TGP.html" | grep -B1 "</a><br>" | grep -v "<img" | grep -v "^-" | replace "</a><br>" "<br></a>"
echo "</body></html>"
