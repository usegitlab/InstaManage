<?php
chdir(__DIR__.'/../');
set_time_limit(0);
date_default_timezone_set('UTC');
require 'vendor/autoload.php';
$logininfo = json_decode($argv[1], 1);
$username = $logininfo[0];
$password = $logininfo[1];
$mode = $logininfo[2];
$proxy = $logininfo[3];
while (true) {
  try {
    $ig = new \InstagramAPI\Instagram(1, 0, ['storage' => 'file', 'basefolder' => DIR.'/ig_sessions/']);
    if ($proxy !== 'off') $ig->setProxy($proxy);
    $loginResponse = $ig->login($username, $password);
    while (true) {
      $messaggi = $ig->direct->getInbox()->getInbox();
      foreach($messaggi->getThreads() as $messaggio){
        $fine = $messaggio->getThreadId();
      }
      $ig->direct->hideThread($fine);
      //sleep(rand(0.7,2.5));
    }
  } catch (Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
  }
}
