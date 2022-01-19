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

$ig = new \InstagramAPI\Instagram(1, 0, ['storage' => 'file', 'basefolder' => DIR.'/ig_sessions/']);
if ($proxy !== 'off') $ig->setProxy($proxy);
$ig->login($username, $password);

while (true) {
  $archiveItems = $ig->timeline->getArchivedMediaFeed()->getItems();
  try {
    foreach($archiveItems as $item) {
      //$xd = $ig->media->getInfo($item->getPk())->getItems()[0]->getMediaType();
      $ig->media->delete($item->getId());
      usleep(rand(0.6*1000000, 1.4*1000000));
    }
  } catch(Exception $e){
    echo 'Something went wrong: '.$e->getMessage()."\n";
  }
}
