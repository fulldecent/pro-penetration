#
# NEXTDOOR-MODELS.COM WEBRIP SCRIPTS
#
# from bash, source commands.bash, then run functions
# or copy/paste commands
#

function config
{
	OPTS="--no-host-directories --directory-prefix siterip --page-requisites --relative"
#	OPTS="$OPTS --no-verbose"
	OPTS="$OPTS --limit-rate=400k --wait=3 --random-wait"
	RECUR="--no-parent --no-clobber --recursive"
	AGENT='--user-agent=""'

	EXCLUDE="--exclude-directories=/*/faq"
	EXCLUDE="$EXCLUDE,/*/thumbs_new*/*,/*/thumbs_new/,/*/pics,/*/games,/*/images"
	EXCLUDE="$EXCLUDE --reject=*jpg,*JPG,*jpeg,*JPEG,*gif,*GIF,*mp3,*MP3,*exe,*EXE,*faq*"

	LOGIN="--user=ndwagner --password=1308ndw"

	OPTIONS="$OPTS $RECUR $AGENT $LOGIN $EXCLUDE"
	echo "Set wget options: $OPTIONS"
}

function clean
{
	find siterip -empty -print0 | xargs -0 rmdir
	echo "Cleaned files"
}

function get
{
	wget $OPTIONS nextdoor-models.com/members/members.htm
}

function extract
{
	# FIND VIDEOS AND SAVE THEM
	find siterip -iname '*.mpg' |
	while read FILE
	do SET=$(grep -l $(basename "$FILE") siterip/members2/videos/*)
	TITLE=$(sed -ne '/Video/ s/\W*\(.\+\) Videos Page.*/\1/p' "$SET")

	mkdir -p "Videos/$TITLE"
	cp -ul "$FILE" "Videos/$TITLE"
	echo "$FILE saved to Videos/$TITLE/"
	done
}
