<?php

include("conf.php");
include("util.php");

// deny messages
$msg_deny_reason = array(
  'user_bytes' => 'You exceeded your download limit for today.',
  'user_connections' => 'You have too many downloads at the same time.'
);

// globals (for the cancel_download)
$download_username = '';
$download_len = 0;
$download_start_time = 0;
$download_url = '';
$remove_link_file = '';

function abort_error($code, $msg)
{
  header("HTTP/1.0 $code Error");
  print "<html><body><b>$msg</b></body></html>\n";
  exit;
}

function do_log($str)
{
  $f = fopen("/tmp/download.log", "a");
  fputs($f, "$str\n");
  fclose($f);
}

function check_auth($username, $timestamp, $site,
                    $url_video, $downloads, $auth)
{
  global $conf;

  if (preg_match('/\.\.\//', $url_video)) return 1;

  $cur_ts = time();
  if ($cur_ts < $timestamp - 1800 || $cur_ts > $timestamp + 1800)
    return 1;

  $md5 = md5($username . $timestamp . $site . $url_video . $downloads . $conf['secret']);
  if ($md5 != $auth)
    return 2;
  return 0;
}

function get_cur_time($ts)
{
  $x = gettimeofday();
  return ($x['sec'] - $ts) * 1000000 + $x['usec'];
}

function download_range($f, $link_file, $chunk_size, $range)
{
  global $download_len;

  $ts = time();
  $start_time = get_cur_time($ts);
  $chunk_size = (int) ($chunk_size / 2);
  $cur = 0;
  $len = $range[1] - $range[0] + 1;
  fseek($f, $range[0], SEEK_SET);
  while ($cur < $len) {
    set_time_limit(20);
    // decide how much to read
    if ($len - $cur >= $chunk_size)
      $r = $chunk_size;
    else
      $r = $len - $cur;
    $cur += $r;

    // read and send
    $str = fread($f, $r);
    print($str);
    flush();
    $now_time = get_cur_time($ts);
    $sleep_usec = 490000 - ($now_time - $start_time);
    if ($sleep_usec > 0) usleep($sleep_usec);
    $start_time = get_cur_time($ts);
    //usleep(910000/2);

    // update status
    touch($link_file);
    $download_len = $cur;
  }

  $download_len = $len;
}

function update_master_log_file($dir, $file, $amount)
{
  $err = 0;
  $link = "$dir/$file.lock";
  $tries = 5;
  while ($tries > 0 && @symlink($dir, $link) == FALSE) {
    sleep(1);
    $tries--;
  }
  if ($tries == 0)
    return 1;

  // read current size
  $f = @fopen("$dir/$file", "r");
  $size = 0;
  if ($f) {
    $size = fread($f, 1024);
    fclose($f);
  }

  $size += $amount;

  // write current size plus amount
  $f = fopen("$dir/$file", "w");
  if ($f) {
    fputs($f, $size);
    fclose($f);
  } else
    $err = 1;

  unlink($link);
  return $err;
}

function cancel_download()
{
  global $conf;
  global $remove_link_file, $download_start_time, $download_len, $download_url;
  global $download_site, $download_username, $must_run_cancel;

  $site = $download_site;

  if (! $must_run_cancel) return;
  $must_run_cancel = 0;

  // update user download stats
  $ts = time();
  list($dir, $log_file) = get_user_log_file($site['user_dir'],
                                            $download_username, $ts);
  mkdir_p($dir, 0755);
  $f = @fopen("$dir/$log_file", "a");
  if ($f) {
    $misc_field = encode_misc_field(array('ip' => $_SERVER['REMOTE_ADDR']));
    fputs($f, "$download_start_time:$ts:$misc_field:$download_len:$download_url\n");
    fclose($f);
  }
  list($dir, $log_file) = get_master_log_file($conf['dir'], $ts);
  update_master_log_file($dir, $log_file, $download_len);
  list($dir, $log_file) = get_master_log_file($site['master_dir'], $ts);
  update_master_log_file($dir, $log_file, $download_len);

  // remove download link file
  unlink($remove_link_file);
}

function do_download($username, $site, $url, $file, $speed)
{
  global $conf, $download_username, $remove_link_file, $download_start_time;
  global $download_url, $download_site, $must_run_cancel;

  $download_username = $username;
  $download_url = $url;
  $download_site = $site;
  $download_start_time = time();

  // check the range
  $file_size = filesize($file);
  $partial = 0;
  $h = getallheaders();
  foreach ($h as $k => $v)
    if (strtolower($k) == 'range') {
      // got the range request
      $txt_range = preg_replace('/^bytes\s*=(.*?)(\/[0-9]+)?\s*$/i', '$1', $v);
      if (preg_match('/=/', $txt_range))
        do_error(416, '1005');
      $list_ranges = explode(',', $txt_range);
      if (sizeof($list_ranges) > 1)
        do_error(416, '1005');
      list($start, $end) = explode('-', $list_ranges[0]);
      $start = trim($start);
      $end = trim($end);
      if ($start == '') {
        $start = $file_size - 1 - $end;
        $end = $file_size - 1;
      } else if ($end == '')
        $end = $file_size - 1;
      if ($start < 0 || $end >= $file_size)
        do_error(416, '1005');
      $range = array($start, $end);
      $partial = 1;
    }
  if (! $partial)
    $range = array(0, $file_size - 1);

  // send headers
  if (preg_match('/mov$/i', $file))
    $type = "video/quicktime";
  else if (preg_match('/wma$/i', $file))
    $type = "application/octet-stream";  // FIXME?
  else
    $type = "application/octet-stream";

  if ($partial) {
    header("HTTP/1.1 206 Partial Content");
    $l = ($range[1] - $range[0] + 1);
    header("Content-Length: $l");
  } else
    header("Content-Length: " . filesize($file));
  header("Content-type: $type");
  header("Accept-Ranges: bytes");

  $x = explode('/', $file);
  if (sizeof($x) > 0)
    $file_only = $x[sizeof($x)-1];
  else
    $file_only = $file;
  header("Content-Disposition: inline; filename=\"" . $file_only . "\"");

  // setup link
  $pid = getmypid();
  $f = preg_replace('/\//', '|', $file);
  $link_file = "$site[user_dir]/${username}=${pid}=${f}";
  touch($link_file);

  $remove_link_file = $link_file;
  $must_run_cancel = 1;
  register_shutdown_function('cancel_download');

  // download
  $chunk_size = 1024 * $speed;
  $f = fopen($file, "rb");
  download_range($f, $link_file, $chunk_size, $range);
  fclose($f);

  // update stats and remove the link
  cancel_download();
}

function do_cleanup($site_name)
{
  global $conf;

  $site = $conf['site'][$site_name];
  $link_timeout = get_site_conf($site_name, 'link_timeout');

  // check timestamp of all links
  $rm_list = array();
  $cur_time = time();
  clearstatcache();
  $dir = @opendir($site['user_dir']);
  if (! $dir) do_error('error 1003');
  while (($file = readdir($dir)) !== false) {
    if ($file[0] == '.') continue;
    $st = @stat("$site[user_dir]/$file");
    $time = $st[9];  // modification time
    if ($cur_time > $time + $link_timeout)
      array_push($rm_list, $file);
  }
  closedir($dir);

  // remove old (dead) links
  foreach ($rm_list as $f)
    @unlink("$site[user_dir]/$f");
}

$username   = $_GET['username'];
$timestamp  = $_GET['timestamp'];
$site_name  = $_GET['site'];
$url_video  = $_GET['url_video'];
$downloads  = $_GET['user_downloads'];
$auth       = $_GET['auth'];
$err = check_auth($username, $timestamp, $site_name,
                  $url_video, $downloads, $auth);
if ($err != 0) {
  do_error(500, "error 1001");
}

if (! array_key_exists($site_name, $conf['site'])) {
  do_error(500, 'error 1002');
}

while ($url_video[0] == '/') $url_video = substr($url_video, 1);
$video_file = $conf['site'][$site_name]['pub'] . "/$url_video";
if (! file_exists($video_file)) {
  do_error(500, 'error 1004');
}

$ret = get_site_info($site_name, $username, $url_video);
if (! $ret['available']) {
  $msg = $msg_deny_reason[$ret['deny_reason']];
  if ($msg == '') $msg = 'error 1006';
  do_error(500, $msg);
}

$speed_per_user = get_site_conf($site, 'speed_per_user');
do_cleanup($site_name);
do_download($username, $conf['site'][$site_name], $url_video,
            $video_file, (($downloads < 0)
                          ? $speed_per_user
                          : $speed_per_user/($downloads+1)));

?>
