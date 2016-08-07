<?php

$conf['slave'] = array();
$conf['slave']['ddg2'] = array(
  'pref'    => 1000,
  'timeout' => 2,
  'url'     => 'http://ddg2.ddgirls.com/scripts/dvd/slave',
  'secret'  => '8bad181601dec0e3836464c368408757'
);
$conf['slave']['ddg3'] = array(
  'pref'    => 1000,
  'timeout' => 2,
  'url'     => 'http://ddg3.ddgirls.com/scripts/dvd/slave',
  'secret'  => 'd9453a75555fd89a12dba80ccadae906'
);
$conf['slave']['ddg1'] = array(
  'pref'    => 1,
  'timeout' => 2,
  'url'     => 'http://ddg1.ddgirls.com/scripts/dvd/slave',
  'secret'  => '0b9fc964e4366a5dc3bab7d312905d40'
);
/*
$conf['slave']['ddg3'] = array(
  'pref'    => 20,
//  'time_pref'  => '0000-0200:2;2300-2359:2',
  'timeout' => 3,
  'url'     => 'http://ddg3.ddgirls.com/scripts/dvd/slave',
  'username'=> 'ddgtest4',
  'password'=> '4test',
  'secret'  => '16fe6e0985b82338b469e1a3c6e9bcee'
);
*/

// deny messages
$msg_deny_reason = array(
  'user_bytes' => 'You exceeded your download limit for today. See more <a href="exceeded.html">here</a>',
  'user_connections' => 'You have too many downloads at the same time.',
  'master_connections' => 'Server too busy, try again later.',
  'site_connections' => 'Server too busy, try again later.'
);

$conf['timeout'] = 10;
$conf['site_name'] = 'ddgirls';

?>
