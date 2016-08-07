#
# NEXTDOORNIKKI.COM WEBRIP SCRIPTS
#

function config
{
	OPTS="--no-host-directories --directory-prefix siterip --page-requisites"
	OPTS="$OPTS --no-verbose"
	RECUR="--no-parent --no-clobber --recursive"
	AGENT='--user-agent=""'

	EXCLUDE="--exclude-directories=/images,/chat,/graphics,/content/webcam,/guest"
	EXCLUDE="$EXCLUDE,/video"

	LOGIN="--user=latour --password=mascott"

	OPTIONS="$OPTS $RECUR $AGENT $LOGIN $EXCLUDE"
	echo "Set wget options: $OPTIONS"
}

function clean
{
	grep -q '</script>' siterip/index.html && sed -ie '1,\@</script>@d' siterip/index.html
	rm siterip/*php*
	echo "Cleaned files"
}

function get
{
	wget $OPTIONS members.nextdoornikki.com
}
