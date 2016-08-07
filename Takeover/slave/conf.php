<?php

$conf['site'] = array();

$conf['site']['ddgirls'] = array(
   'master_dir'               => '/logs/sites/ddgirls.com/dvd/master',
   'user_dir'                 => '/logs/sites/ddgirls.com/dvd/users',
   'pub'                      => '/opt/sites/ddgirls.com/public_html',
   'link_timeout'             => 10,
   'max_connections'          => 250,
   'max_connections_per_user' => 6,
   'speed_per_user'           => 100,
   'max_download'             => 400 * 1024 * 1024 * 1024,
   'max_download_per_user'    => 1000 * 1024 * 1024
);

// global options:
$conf['dir'] = '/logs/sites/ddgirls.com/dvd/global_master';
$conf['master_max_download'] = 350 * 1024 * 1024 * 1024;
$conf['secret'] = 'd9453a75555fd89a12dba80ccadae906';

// overridable options in the sites:
$conf['link_timeout'] = 10;
$conf['max_connections'] = 20;
$conf['speed_per_user'] = 192;
$conf['max_download'] = 30 * 1024 * 1024;
$conf['max_download_per_user'] = 1000 * 1024 * 1024;
$conf['allow_ips'] = "64.124.49.86/26, 64.124.49.112, 67.43.172.26/21, 200.207.153.251";

?>
