#
# DDGIRLS.COM ACCESS WEBRIP SCRIPTS
#

OPTS="--no-host-directories --directory-prefix site --page-requisites"
OPTS="$OPTS --no-verbose"
RECUR="--no-parent --no-clobber --recursive"
AGENT='--user-agent=""'
LOGIN="--user=Jamie123 --password=magic"
#LOGIN="--user=steffe63 --password=foto63"
#LOGIN="--user=dlb1234 --password=1234dlb"
#LOGIN="--user=cbaier --password=kn1pers"
#LOGIN="--user=jefburns --password=jonburns-ALLSTONBEAT"
#LOGIN="--user=Ants8670 --password=Anthony"
REJECT='--reject=*php'

# Ignore low res files
EXCLUDE="--exclude-directories=/art,/*/*/*/*/zip,/*/*/*/*/*/zip"
EXCLUDE="$EXCLUDE,/*/*/*/*/*/index*,/*/*/*/*/*/tn1,/*/*/*/*/*/res4,/*/*/*/*/*/res3,/*/*/*/*/*/res2"
EXCLUDE="$EXCLUDE,/*/*/*/*/*/*/index*,/*/*/*/*/*/*/tn1,/*/*/*/*/*/*/res4,/*/*/*/*/*/*/res3,/*/*/*/*/*/*/res2"
EXCLUDE="$EXCLUDE,/*/*/*/*/*/*/*/index*,/*/*/*/*/*/*/*/tn1,/*/*/*/*/*/*/*/res4,/*/*/*/*/*/*/*/res3,/*/*/*/*/*/*/*/res2"
EXCLUDE="$EXCLUDE,/*/scripts,/*/*/*/*/*/tv,/*/*/*/*/*/*/tv,/*/*/*/*/*/*/wm,/*/*/*/*/*/wm"
EXCLUDE="$EXCLUDE,/rank,/terms_files,/tour,/ztour,/members/directory,/members/static"
EXCLUDE="$EXCLUDE,/members/intight"

OPTIONS="$OPTS $RECUR $AGENT $LOGIN $REJECT $EXCLUDE"

rm site/members/dreamgirls/pub/index.html

wget $OPTIONS ddgirls.com/members/dreamgirls/
wget $OPTIONS ddgirls.com/members/creamgirls/
wget $OPTIONS ddgirls.com/members/covergirl/
wget $OPTIONS ddgirls.com/members/strip/
wget $OPTIONS ddgirls.com/members/dreamflicks/
wget $OPTIONS ddgirls.com/members/ddgx/flixxx/
#wget $OPTIONS ddgirls.com/members/daily/ # SUCKS
#wget $OPTIONS ddgzine.com/members/videos/ # WONT WORK


# www.ddgzine.com/members/seasonal/index.html
# www.ddgzine.com/members/brazil/index.html
# www.ddgzine.com/members/talent_search/index.html
# www.ddgzine.com/members/dailyjsh/index.html

#create temp files
mkdir -p site/members/strip/pub
echo "<a href='pub/index.html'>" > site/members/strip/index.html

wget $OPTS $RECUR $LOGIN $EXCLUDE ddgirls.com/members/strip/index.html

# VIDEO URLS NEED TO BE REWRITTEN: good example, bad, bad, bad:
# ddgirls.com/members/scripts/dvd/master/find.php?url_video=/members/ddgx/naughtyvids/2006_02/18/110552x/qt/110552x_dsl.zip
# ddgirls.com/members/scripts/dvd/master/find.php?url_video=/members/ddgx/naughtyvids/2006_02/18/110552x/qt/110552x_56k.zip
# ddgirls.com/members/scripts/dvd/master/find.php?url_video=/members/ddgx/naughtyvids/2006_02/18/110552x/wm/110552x_preview.wmv
# ddgirls.com/members/scripts/dvd/master/find.php?url_video=/members/ddgx/naughtyvids/2006_02/18/110552x/wm/110552x_56k.wmv
# ddgirls.com/members/scripts/dvd/master/find.php?url_video=/members/ddgx/naughtyvids/2006_02/18/110552x/wm/110552x_dsl.wmv
find -name index.html -print0 | xargs -0 replace "_56k" "_dsl" --
find -name index.html -print0 | xargs -0 replace "_preview" "_dsl" --
find -name index.html -print0 | xargs -0 replace "/wm/" "/qt/" --
find -name index.html -print0 | xargs -0 replace ".wmv" ".zip" --
find -name index.html -print0 | xargs -0 replace "/members/scripts/dvd/master/find.php?url_video=/members" "/members" --
# after this, rerun the WGET

# FIND PHOTOS SETS AND SAVE THEM
find -iname res1| grep -v pub |
while read DIR
do INFO=$(echo $DIR | sed -e 's|\(/[^/]\+/[^/]\+/[^/]\+\)/res1$|/pub\1/index.html|g')
NAME=$(basename $(grep -o "/members/directory/.*html" $INFO | head -n 1) .html)
echo $NAME

mkdir -p "Photos/$NAME"
cp -ul $DIR/* Photos/$NAME
done

# FIND VIDEOS AND SAVE THEM
find -iname '*_dsl.zip' |
while read FILE
do INFO=$(echo $FILE | sed -e 's|\(/[^/]\+/[^/]\+/[^/]\+\)/qt.*_dsl.zip$|/pub\1/index.html|g')
NAME="$(grep '<span class="video_name">' $INFO | sed -e 's/.*>\([^<]\+\)<.*/\1/' | head -n 1)"

mkdir -p "Videos/$NAME"
unzip -nj $FILE -d "Videos/$NAME"
echo $FILE
ls -l "Videos/$NAME"
echo
done

# Check the schedule to get items pre-release
# Only works for image files, not indexes
