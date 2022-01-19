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
$userID = $ig->account->getCurrentUser()->getUser()->getPk();
$searchQuery = null;

try {
  $rankToken = \InstagramAPI\Signatures::generateUUID();
  $maxId = null;
  $num = 1;
  do {
    $response = $ig->people->getFollowing($userID,$rankToken,$searchQuery,$maxId);
    foreach($response->getUsers() as $users){
      $ig->people->unfollow($users->getPk());
      echo $users->getPk().' #'.$num."unfollowato\n";
      $num++;
      sleep(rand(12,27));
    }
    $maxId = $response->getNextMaxId();
    echo "pausa \n";
    sleep(5);
  } while ($maxId !== null);
} catch (\Exception $e) {
  echo 'Something went wrong: '.$e->getMessage()."\n";
  exit;
}
