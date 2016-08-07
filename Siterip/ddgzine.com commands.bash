#
# DDGIRLS.COM WEBRIP SCRIPTS
#
# from bash, source commands.bash, then run functions
# or copy/paste commands
#

function config
{
    OPTS="--no-host-directories --directory-prefix siterip --page-requisites"
    OPTS="$OPTS --no-verbose"
    RECUR="--no-parent --no-clobber --recursive"
    AGENT='--user-agent=""'
    LOGIN="--user=walnls --password=agatha"
    REJECT='--reject=*php,*.gif%5C%22,*.gif,bt_addedtomyfavorites*'
    #EXTRA='--ignore-tags=img'
    EXTRA=''

    # Ignore low res files
    EXCLUDE="--exclude-directories=/*/*/*/*/*/*/art,/art,/*/*/*/*/zip,/*/*/*/*/*/zip"
    EXCLUDE="$EXCLUDE,/*/*/*/*/*/index*,/*/*/*/*/*/tn1,/*/*/*/*/*/res4,/*/*/*/*/*/res3,/*/*/*/*/*/res2"
    EXCLUDE="$EXCLUDE,/*/*/*/*/*/*/index*,/*/*/*/*/*/*/tn1,/*/*/*/*/*/*/res4,/*/*/*/*/*/*/res3,/*/*/*/*/*/*/res2"
    EXCLUDE="$EXCLUDE,/*/*/*/*/*/*/*/index*,/*/*/*/*/*/*/*/tn1,/*/*/*/*/*/*/*/res4,/*/*/*/*/*/*/*/res3,/*/*/*/*/*/*/*/res2"
    EXCLUDE="$EXCLUDE,/*/scripts,/*/*/*/*/*/tv,/*/*/*/*/*/*/tv,/*/*/*/*/*/*/wm,/*/*/*/*/*/wm"
    EXCLUDE="$EXCLUDE,/rank,/terms_files,/tour,/ztour,/members/directory,/members/static"
    EXCLUDE="$EXCLUDE,/members/intight,/members/*/pub/*/*/*/*/art"

    OPTIONS="$OPTS $RECUR $AGENT $LOGIN $REJECT $EXCLUDE $EXTRA"

    echo "Set wget options: $OPTIONS"
}

function clean
{
    SUCKY_SECTIONS="daily seasonal brazil talent_search dailyjsh"

    SECTIONS="dreamgirls creamgirls covergirl strip just19"
    SECTIONS="$SECTIONS dreamflicks videos"
    SECTIONS="$SECTIONS ddgx/naughtyvids ddgx/flixxx"
    for section in $SECTIONS
    do
        mkdir -p siterip/members/$section/pub
        rm -f siterip/members/$section/pub/index.html
        rm -f siterip/members/$section/pub/month_index_thumb.html
        echo "<a href='pub/month_index_thumb.html'>" > siterip/members/$section/index.html

        find siterip/members/$section -name index.html -print0 | xargs -0 replace "_56k" "_dsl" "_preview" "_dsl" "/wm/" "/qt/" ".wmv" ".zip" --
        find siterip/members/$section -name index.html -print0 | xargs -0 replace "/members/scripts/dvd/master/find.php?url_video=/members" "/members" --
    done

    #find siterip -empty -print0 | xargs -0 rmdir
    echo "Cleaned files"
}

function get
{
    wget $OPTIONS ddgirls.com/members/dreamgirls/
    wget $OPTIONS ddgirls.com/members/creamgirls/
    wget $OPTIONS ddgirls.com/members/covergirl/
    wget $OPTIONS ddgirls.com/members/strip/
    wget $OPTIONS ddgirls.com/members/just19/

    wget $OPTIONS ddgirls.com/members/dreamflicks/
    wget $OPTIONS ddgirls.com/members/videos/

    wget $OPTIONS ddgirls.com/members/ddgx/naughtyvids/
    wget $OPTIONS ddgirls.com/members/ddgx/flixxx/
}

function extract
{
    # copy photo assets to Photos
    find siterip -iname res1| grep -v pub |
    while read DIR
        do INFO=$(echo $DIR | sed -e 's|\(/[^/]\+/[^/]\+/[^/]\+\)/res1$|/pub\1/index.html|g')
        NAME=$(basename $(grep -o "/members/directory/.*html" $INFO | head -n 1) .html)
        echo $NAME

        mkdir -p "Photos/$NAME"
        cp -ul $DIR/* Photos/$NAME
    done

    # copy video assets to Videos
    find -iname '*_dsl.zip' |
    while read FILE
        do
        BASE=$(echo $FILE | sed -e 's|.*/\([^/]\+\)_dsl.zip$|\1|g')
        INFO=$(echo $FILE | sed -e 's|\(/[^/]\+/[^/]\+/[^/]\+\)/qt.*_dsl.zip$|/pub\1/index.html|g')
        NAME="$(sed -n '/<span class="video_name">/s/.*>\([^<]\+\)<.*/\1/p' $INFO)"
        [ "$NAME" == "" ] && NAME="$(sed -n '/video_modelname/s/.*>\([^<]\+\)<.*/\1/p' $INFO)"


        if [ -f "Videos/$NAME/${BASE}_dsl.mov" ]; then
            echo -n 'Exists: '
            ls "Videos/$NAME/${BASE}_dsl.mov"
        else
            mkdir -p "Videos/$NAME"
            echo "Zip: $FILE"
            nice unzip -nj $FILE -d "Videos/$NAME"
            ls -h "Videos/$NAME"
        fi
    done
}
