<?php

include("conf.php");
include("util.php");

function abort_error($code, $msg)
{
  $ret = array('error' => $msg);
  print_output($ret);
  exit;
}

function debug_log($str)
{
  global $xxx_debug_log;

  $xxx_debug_log++;
  print "log$xxx_debug_log=$str\n";
}

function do_log($str)
{
  $f = fopen("/tmp/info.log", "a");
  fputs($f, "$str\n");
  fclose($f);
}

function print_output($out)
{
  foreach ($out as $k => $v)
    print "$k = $v\n";
}

function check_remote_ip($allowed_list, $remote_ip)
{
  $ip = explode('.', $remote_ip);
  $remote_ip_num = ($ip[0] << 24) | ($ip[1] << 16) | ($ip[2] << 8) | $ip[3];

  foreach ($allowed_list as $allow) {
    $allow = trim($allow);
    list($ip_text, $net) = explode('/', $allow);
    if ($net == '') $net = 32;
    $ip = explode('.', $ip_text);
    $ip_num = ($ip[0] << 24) | ($ip[1] << 16) | ($ip[2] << 8) | $ip[3];
    if ($net == 32)
      $net_num = 0xffffffff;
    else
      $net_num = ((0xffffffff >> (32-$net)) << (32-$net));
    if (($remote_ip_num & $net_num) == ($ip_num & $net_num))
      return 1;
  }
  return 0;
}

function check_auth($username, $timestamp, $site, $url_video, $auth)
{
  global $conf;

  if (preg_match('/\.\.\//', $url_video)
      || preg_match('/\/\.\./', $url_video) ) return 1;

  $cur_ts = time();

  if ($cur_ts < $timestamp - 1800 || $cur_ts > $timestamp + 1800)
    return 1;

  $md5 = md5($username . $timestamp . $site . $url_video . $conf['secret']);
  if ($md5 != $auth)
    return 2;
  return 0;
}

// check authorization
$username   = $_GET['username'];
$timestamp  = $_GET['timestamp'];
$site_name  = $_GET['site'];
$url_video  = $_GET['url_video'];
$auth       = $_GET['auth'];

$allow_ips = get_site_conf($site_name, 'allow_ips');
if (! check_remote_ip(explode(',', $allow_ips), $_SERVER['REMOTE_ADDR'])) {
  abort_error(500, 'error 1000');
}

// check authorization
$err = check_auth($username, $timestamp, $site_name, $url_video, $auth);
if ($err != 0) {
  abort_error(500, 'error 1001');
}

// check if site exists here
if (! array_key_exists($site_name, $conf['site'])) {
  abort_error(500, 'error 1002');
}

$ret = get_site_info($site_name, $username, $url_video);

print_output($ret);

?>
