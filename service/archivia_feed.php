<?php

/*SERVICE ARCHIVE POST*/


///////CONFIG///////
chdir(__DIR__.'/../');
set_time_limit(0);
date_default_timezone_set('UTC');
require 'vendor/autoload.php';
$logininfo = json_decode($argv[1], 1);
$username = $logininfo[0];
$password = $logininfo[1];
$mode = $logininfo[2];
$proxy = $logininfo[3];

if ($mode === 'all') {
  $delete_mode = 'all';
} else {
  $mInfo = explode(';', $mode);
  if ($mInfo[0] === 'lowLikes') {
    $delete_mode = 'if_like';
    $_top = $mInfo[1];
  } elseif ($mInfo[0] === 'lowViews') {
    $delete_mode = 'if_low';
    $top = $mInfo[1];
  } else {
    echo 'Error: invalid mode - '.$mode;
  }
}

$ig = new \InstagramAPI\Instagram(1, 0, ['storage' => 'file', 'basefolder' => DIR.'/ig_sessions/']);
if ($proxy !== 'off') $ig->setProxy($proxy);
$loginResponse = $ig->login($username, $password);

$selfItems = $ig->timeline->getSelfUserFeed()->getItems();
////////////////



//////LETS DICHIARARE FUNZIONI//////


//getLikes
function getLikes($cazzo){
  global $ig; global $selfItems;
  return $ig->media->getInfo($cazzo)->getItems()[0]->getLikeCount();
}
//cacata
function rSleep(){
  return sleep(rand(0.7,1.6));
}


//function count views
function getViews($roba){
  global $ig;
  return $ig->media->getInfo($roba)->getItems()[0]->getViewCount();
}


//check is video function
function is_video($video){
  global $ig;
  if($ig->media->getInfo($video)->getItems()[0]->getMediaType() == '2'){
    return true;
  }else{
    return false;
  }
}

//$roba = $ig->timeline->getSelfUserFeed()->getItems()[5]->getPk();

//ARCHIVIA SOLO VIDEO $views < $PARAM


if($delete_mode == 'if_low'){
  try{
    global $selfItems;
    foreach($selfItems as $uno){
      $pkk = $uno->getPk();
      if(is_video($pkk)){
        if((int) $uno < (int) $top){
          $ig->timeline->archiveMedia($pkk);
        }
      }else{
        return true;
      }
    }
  }catch(Exception $e){
    echo 'Something went wrong: '.$e->getMessage()."\n";
  }
}


//ARCHIVIA TUTTO
if($delete_mode == 'all'){
  $selfItem = $ig->timeline->getSelfUserFeed()->getItems();
  try{
    foreach($selfItem as $archiveds){
      $ig->timeline->archiveMedia($archiveds->getPk());
      rSleep();
    }
  }catch(Exception $e) {
    echo 'Something went wrong: '.$e->getMessage()."\n";
  }
}

//ARCHIVIA SOLO SE LIKE SONO INFERIORI A $_top
if($delete_mode == 'if_like'){
  try{
    global $selfItems;
    foreach($selfItems as $photos){
      $id = $photos->getPk();
      if(!is_video($id)){
        if((int) getLikes($id) < (int) $_top)
        {
          $ig->timeline->archiveMedia($photos);
        }else{

        }

      }
    }
  }catch(Exception $e){
    echo 'Something went wrong: '.$e->getMessage()."\n";
  }
}
