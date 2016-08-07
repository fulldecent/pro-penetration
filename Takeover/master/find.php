<?php

include("conf.php");

$site_name = $conf['site_name'];
$username = $_SERVER['REMOTE_USER'];
if ($username == '') $username = 'joe';
$url_video = $_GET['url_video'];

if ($url_video == '') {
  print "<html><body>Invalid download file name.</body></html>\n";
  exit();
}

$conf['debug'] = 0;

function do_error($msg)
{
  global $conf;

  if ($conf['debug'] == 0) {
    header("HTTP/1.0 503 Bandwidth Limit Exceeded");
    print "<html><body><b>$msg</b></body></html>\n";
  } else {
    print "<pre>No good slave found!</pre>\n";
    print "<b>$msg</b>\n";
  }
  exit;
}

function redirect($url)
{
  header("Location: $url");
  exit;
}

function get_url($url, $timeout, $username, $password)
{
  if (! extension_loaded("curl"))
    dl("curl.so");

  $ch = curl_init();
  if (! $ch)
    return array(1, '');
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_HEADER, 0);
  curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  if ($username != '')
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
  $out = curl_exec($ch);
  curl_close($ch);

  return array(0, $out);
}

function process_slave($slave, $site_name, $username, $url_video)
{
  global $conf;

  // make the request
  $time = time();
  $md5 = md5($username . $time . $site_name . $url_video . $slave['secret']);
  $s_username = urlencode($username);
  $s_site_name = urlencode($site_name);
  $s_url_video = urlencode($url_video);
  $timeout = $slave['timeout'];
  if ($timeout == '') $timeout = $conf['timeout'];
  $info_url = "$slave[url]/info.php?username=$s_username&timestamp=$time&site=$s_site_name&url_video=$s_url_video&auth=$md5";
  $ret = get_url($info_url, $timeout, $slave['username'], $slave['password']);

  if ($ret[0] != 0)
    return array();

  // parse the output
  $lines = explode("\n", $ret[1]);
  $ret = array('info_url' => $info_url);
  foreach ($lines as $line) {
    list($key, $val) = explode('=', $line, 2);
    $key = trim($key);
    $val = trim($val);
    if ($key == '') continue;
    $ret[$key] = $val;
  }

  return $ret;
}

/* pick a random slave from $cands[] weighting their grades */
function elect_slave(&$cands)
{
  list($usec, $sec) = explode(" ", microtime());
  $seed = ((float)$usec + (float)($sec % 1000)); 
  srand((int) ($seed * 1000));

  // get the number of buckets
  $num_buckets = 0;
  foreach ($cands as $name => $cand) {
    if (! $cand['available'] || $cand['grade'] <= 0) continue;
    $num_buckets += $cand['grade'];
  }
  if ($num_buckets <= 0)
    return '';     /* no good graded slaves */

  // pick a bucket
  $bucket = rand(0, $num_buckets - 1);

  // see whose bucket it is
  foreach ($cands as $name => $cand) {
    if (! $cand['available']) continue;
    if ($bucket < $cand['grade'])
      return $name;
    $bucket -= $cand['grade'];
  }

  // we should never arrive here, but... we'll be nice and return the
  // first available slave
  foreach ($cands as $name => $cand)
    if ($cand['available'])
      return $name;

  return '';
}

function get_today_time_ts($time, $ts)
{
  if (! preg_match('/^([0-9][0-9])([0-9][0-9])$/', $time, $r))
    return -1;
  $hour = $r[1];
  $min = $r[2];
  $date_ts = mktime($hour, $min, 0,
                    date("m", $ts), date("d", $ts), date("Y", $ts));
  return $date_ts;
}

function get_slave_time_pref($slave, $ts)
{
  $str = explode(';', $slave['time_pref']);
  foreach ($str as $s) {
    list($start_end, $pref) = explode(':', $s);
    list($start, $end) = explode('-', $start_end);
    $start_ts = get_today_time_ts($start, $ts);
    $end_ts = get_today_time_ts($end, $ts);
    if ($start_ts <= $ts && $ts <= $end_ts)
      return $pref;
  }
  return 1;
}

function select_slave($site_name, $username, $url_video, &$info)
{
  global $conf, $msg_deny_reason;

  if ($conf['debug'])
    print "<b>Querying slaves...</b>\n<hr>\n";

  // query all the slaves
  $cands = array();
  $total_user_downloads = 0;
  foreach ($conf['slave'] as $name => $slave) {
    if ($slave['disable']) continue;
    $time_pref = get_slave_time_pref($slave, time());
    if ($time_pref == 0) {
      $cands[$name] = array('time_pref' => $time_pref);
      continue;
    }
    $ret = process_slave($slave, $site_name, $username, $url_video);
    $ret['time_pref'] = $time_pref;
    $total_user_downloads += $ret['user_connections'];
    if ($ret['max_connections'] <= 0 || $ret['max_bytes_today'] <= 0) {
      $ret['grade'] = 0;
    } else {
      // grade = pref * (1 - conn/max_conn) * (1 - bytes/max_bytes)
      $ret['grade'] = ($slave['pref']
                       * (1 - ($ret['total_connections']
                               / $ret['max_connections']))
                       * (1 - ($ret['total_bytes_today']
                               / $ret['max_bytes_today'])));
      // grade *= time_pref
      $ret['grade'] *= $ret['time_pref'];
    }
    $cands[$name] = $ret;
  }
  $info['total_user_downloads'] = $total_user_downloads;

  // debug info
  if ($conf['debug']) {
    print "<pre>\n";
    foreach ($cands as $name => $cand) {
      print "SLAVE[<b>$name</b>] = (\n";
      foreach ($cand as $k => $v) {
        if ($k == 'info_url')
          print "  info_url = <a href='" . htmlentities($v) . "'>" . substr($v, 0, 7) . "...</a>\n";
        else
          print "  " . htmlentities($k) . " = '" . htmlentities($v) . "'\n";
      }
      print ")\n\n";
    }
    print "</pre>\n";
  }

  // choose the best slave
  $best_name = elect_slave($cands);
  if ($best_name == '') {
    $reason = '';
    foreach ($cands as $cand) {
      switch ($cand['deny_reason']) {
      case 'user_connections':
        $reason = $cand['deny_reason'];
        break;
      case 'site_connections':
      case 'master_connections':
        if ($reason != 'user_connections')
          $reason = 'master_connections';
        break;
      case 'user_bytes':
        if ($reason != 'user_connections' && $reason != 'user_connections')
          $reason = $cand['deny_reason'];
        break;
      default:
        if ($reason != 'user_connections'
            && $reason != 'user_connections'
            && $reason != 'user_bytes')
          $reason = $cand['deny_reason'];
      }
    }
    $msg = $msg_deny_reason[$reason];
    if ($msg == '')
      $msg = "Server busy, try again.";
    do_error($msg);
  }

  if ($conf['debug'])
    print "<pre>==&gt; SELECTED SLAVE <b>$best_name</b></pre>\n";

  return $conf['slave'][$best_name];
}

if ($conf['debug']) {
  print "<html><body>\n";
}

$info = array();
$winner = select_slave($site_name, $username, $url_video, $info);

// redirect
if ($winner['use_direct']) {
  if ($url_video[0] == '/') $url_video = substr($url_video, 1);
  $download_url = "$winner[direct_url]/$url_video";
} else {
  $time = time();
  $md5 = md5($username . $time . $site_name . $url_video
             . $info['total_user_downloads'] . $winner['secret']);
  $s_username = urlencode($username);
  $s_site_name = urlencode($site_name);
  $s_url_video = urlencode($url_video);
  $s_user_downloads = urlencode($info['total_user_downloads']);
  $download_url = "$winner[url]/download.php?username=$s_username&timestamp=$time&site=$s_site_name&user_downloads=$s_user_downloads&auth=$md5&url_video=$s_url_video";
}

if ($conf['debug']) {
  $x = htmlentities($download_url);
  print "<hr>\n";
  print "Download URL is <a href=\"$x\">here</a>.\n";
  print "</body></html>\n";
} else {
  redirect($download_url);
}

?>
