<?php
// by paolo wu and peppelg
chdir(__DIR__.'/../');
set_time_limit(0);
date_default_timezone_set('UTC');
require 'vendor/autoload.php';
$logininfo = json_decode($argv[1], 1);
$username = $logininfo[0];
$password = $logininfo[1];
$mode = $logininfo[2];
$proxy = $logininfo[3];
if (strpos($mode, 'only_') === 0) { //se deve accettare solo tot richieste
  $rtoaccept = str_replace('only_', '', $mode);
  $mode = 0;
}
$ig = new \InstagramAPI\Instagram(1, 0, ['storage' => 'file', 'basefolder' => DIR.'/ig_sessions/']);
if ($proxy !== 'off') $ig->setProxy($proxy);
$loginResponse = $ig->login($username, $password);
while (true) {
  try {
    $num = 0;
    do {
      echo "Prendo richieste seguitaggio per $username...";
      $response = $ig->people->getPendingFriendships();
      echo " Fatto\n";
      $usersig = $response->getUsers();
      $usersplit = array_chunk($usersig, 50);
      foreach ($usersplit as $userstoaccept) {
        $pids = array();
        $pidn = 0;
        foreach($userstoaccept as $michele) {
          if ($mode === 'super') {
            echo "[super] $pidn; ";
            $pidn++;
            $pids[$pidn] = pcntl_fork();
            if(!$pids[$pidn]) { //nuovo processo
              try {
                $Giuseppe = $michele->getPk();
                $ig->people->approveFriendship($Giuseppe);
              } catch (Exception $e) { }
              unset($Giuseppe);
              unset($ig);
              exit;
            }
          } else { //modalità non è super
            if ($mode === 'random') usleep(rand(0.4*1000000, 1.5*1000000)); elseif(is_numeric($mode)) sleep($mode); //sleep
            try {
              $Giuseppe = $michele->getPk();
              $ig->people->approveFriendship($Giuseppe);
              $num++;
              echo "Accettato #$num\n";
            } catch (Exception $e) { }
          }
        }
        if ($mode === 'super') {
          foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status, WUNTRACED); //aspettiamo che i processi finiscono
            posix_kill($pid, SIGKILL); //bo
          }
          echo "\nRichieste accettate con modalità super.\n";
        }
        if (isset($rtoaccept) and $rtoaccept >= $num) exit; //se ha già accettato tot utenti
        //sleep(1);
      }
    } while (!empty($response->getUsers()));
  } catch (\Exception $e) {
    echo 'errore: '.$e->getMessage()."\n";
  }
  //sleep(2);
}
