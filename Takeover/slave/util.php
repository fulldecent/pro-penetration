<?php

function get_site_conf($site, $option)
{
  global $conf;

  $v = $conf['site'][$site][$option];
  if ($v == '') $v = $conf[$option];
  return $v;
}

function decode_misc_field($field)
{
  $x = explode(';', $field);
  $ret = array();
  foreach ($x as $item) {
    list($name, $val) = explode('=', $item, 2);
    if ($name == '') continue;
    $ret[$name] = urlencode($val);
  }
  return $ret;
}

function encode_misc_field($info)
{
  $field = '';
  foreach ($info as $k => $v) {
    $xk = preg_replace('/[^A-Za-z0-9_]/', '_', $k);
    $field .= $xk . '=' . urlencode($v) . ';';
  }
  return $field;
}

function mkdir_p($target, $mode)
{
  $ps = explode('/', $target);
  $cur = '';
  foreach ($ps as $p) {
    $cur .= "$p/";
    if (@file_exists($cur))
      continue;
    mkdir($cur, $mode);
  }
}

function get_user_log_file($base, $username, $ts)
{
  $dir = "$base/" . date("Y_m/d", $ts);
  if (! @file_exists($dir)) mkdir_p($dir, 0755);
  $file = $username;
  return array($dir, $file);
}

function get_master_log_file($base, $ts)
{
  $dir = "$base/" . date("Y_m/d", $ts);
  if (! @file_exists($dir)) mkdir_p($dir, 0755);
  $file = 'master';
  return array($dir, $file);
}

function deny_slave($deny_file, $reason)
{
  $f = @fopen($deny_file, "w");
  if ($f) {
    fputs($f, $reason);
    fclose($f);
    return 0;
  }
  return 1;
}

function get_deny_reason($deny_file)
{
  $f = @fopen($deny_file, "r");
  if ($f) {
    $str = fgets($f, 1024);
    fclose($f);
    return $str;
  }
  return '';
}

function get_site_info($site_name, $username, $url_video)
{
  global $conf;

  $site = $conf['site'][$site_name];

  // get the limits
  $max_download_per_user = get_site_conf($site_name, 'max_download_per_user');
  $max_download = get_site_conf($site_name, 'max_download');
  $master_max_download = $conf['master_max_download'];

  while ($url_video[0] == '/')
    $url_video = substr($url_video, 1);

  $ret['available'] = (@file_exists("$site[pub]/$url_video")) ? 1 : 0;
  if (! $ret['available'])
    $ret['deny_reason'] = 'file_not_found';
  $ret['max_connections'] = get_site_conf($site_name, 'max_connections');
  $ret['max_connections_per_user'] = get_site_conf($site_name,
                                                   'max_connections_per_user');
  $ret['max_bytes_today'] = $master_max_download;
  $ret['max_site_bytes_today'] = $max_download;

  // get master bytes today
  $ts = time();
  if ($ret['available']) {
    list($dir, $file) = get_master_log_file($conf['dir'], $ts);
    if (@file_exists("$dir/deny")) {
      $ret['available'] = 0;
      $ret['deny_reason'] = get_deny_reason("$dir/deny");
    } else {
      $size = 0;
      $f = @fopen("$dir/$file", "r");
      if ($f) {
        $size = fread($f, 1024);
        fclose($f);
      }
      if ($size > $master_max_download) {
        deny_slave("$dir/deny", 'master_bytes');
        $ret['available'] = 0;
        $ret['deny_reason'] = 'master_bytes';
      }
      $ret['total_bytes_today'] = $size;
    }
  }

  // get site bytes today
  if ($ret['available']) {
    list($dir, $file) = get_master_log_file($site['master_dir'], $ts);
    if (@file_exists("$dir/deny")) {
      $ret['available'] = 0;
      $ret['deny_reason'] = get_deny_reason("$dir/deny");
    } else {
      $size = 0;
      $f = @fopen("$dir/$file", "r");
      if ($f) {
        $size = fread($f, 1024);
        fclose($f);
      }
      if ($size > $max_download) {
        deny_slave("$dir/deny", 'site_bytes');
        $ret['available'] = 0;
        $ret['deny_reason'] = 'site_bytes';
      }
      $ret['site_bytes_today'] = $size;
    }
  }

  // get user bytes today
  if ($ret['available']) {
    $user_bytes_today = 0;
    $ts = time();
    list($dir, $file) = get_user_log_file($site['user_dir'], $username, $ts);
    $day_log = @file("$dir/$file");
    if (is_array($day_log)) {
      foreach ($day_log as $line) {
        $l = split(':', $line);
        if ($l[3] == 'RESET')
          $user_bytes_today = 0;
        else
          $user_bytes_today += $l[3];
      }
    }
    $ret['user_bytes_today'] = $user_bytes_today;
    if ($ret['user_bytes_today'] > $max_download_per_user) {
      $ret['available'] = 0;
      $ret['deny_reason'] = 'user_bytes';
    }
  }

  // get other stats
  $total_conn = 0;
  $user_conn = 0;
  $video_conn = 0;
  $dir = @opendir($site['user_dir']);
  if (! $dir) abort_error(500, 'error 1003');
  while (($file = readdir($dir)) !== false) {
    if ($file[0] == '.' || is_dir("$site[user_dir]/$file")) continue;
    list($u, $pid, $video) = explode('=', $file, 3);
    $video = preg_replace("|^$site[pub]/|", '',
                          preg_replace('/\|/', '/', $video));
    $total_conn++;
    if ($u == $username) $user_conn++;
    if ($video == $url_video) $video_conn++;
  }
  closedir($dir);
  $ret['total_connections'] = $total_conn;
  $ret['user_connections'] = $user_conn;
  $ret['video_connections'] = $video_conn;

  if ($ret['user_connections'] >= $ret['max_connections_per_user']) {
    $ret['available'] = 0;
    $ret['deny_reason'] = 'user_connections';
  }

  return $ret;
}

function list_dir($path, $re = '')
{
  if ($re == '') $re = '/^([^.].*)$/';
  $list = array();
  if ($dir = @opendir($path)) {
    while (($file = readdir($dir)) !== false)
      if (preg_match($re, $file, $r))
	array_push($list, $r[1]);
    closedir($dir);
  }
  sort($list);
  return $list;
}

function get_user_total_bytes($base, $ts)
{
  $dir = "$base/" . date("Y_m/d", $ts);
  if (! @file_exists($dir)) mkdir_p($dir, 0755);

  $total = 0;
  $user_list = list_dir($dir);
  foreach ($user_list as $user) {
    $user_total = 0;
    $file = @file("$dir/$user");
    foreach ($file as $line) {
      $x = explode(':', $line);
      if ($x[3] == 'RESET') continue;
      $user_total += $x[3];
    }
    //print "SITE_$user = $user_total\n";
    $total += $user_total;
  }
  //print "SITE_TOTAL = $total\n";
  return $total;
}

?>
