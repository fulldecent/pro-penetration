#
# BULLZ-EYE.COM WEBRIP SCRIPTS
#

wget -np -nc -r 'http://barney:rubble@www.bullz-eye.com/Models/' --limit-rate=100k
watch replace "tn.jpg" ".jpg" -- * */* */*/*
find -size -30k -print0 | xargs -0 rm
