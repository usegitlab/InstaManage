<?php
define('DEBUG_MODE', false);
date_default_timezone_set('Europe/Rome');
setlocale(LC_ALL, 'it_IT.UTF-8');
require __DIR__.'/../vendor/autoload.php';
function run() {
  $loop = React\EventLoop\Factory::create();
  $processes = array();
  $pdo = new PDO(SQL_CONNECTION, SQL_USER, SQL_PASSWORD, array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false));
  $result = $pdo->prepare('SELECT * FROM ig_bot');
  $result->execute();
  $result = $result->fetchAll();
  echo "Servizio avviato\n";
  foreach ($result as $user) {
    if (isset($user['expdate']) and is_numeric($user['expdate']) and time() > $user['expdate']) $user['ig_username'] = ''; //metodo velox per fare check abbonamento scaduto
    if ($user['ig_username'] and $user['ig_password']) {
      $username = $user['ig_username'];
      $password = $user['ig_password'];
      if ($user['accetta_richieste'] !== 'off') {
        $processes[] = new React\ChildProcess\Process('exec php '.escapeshellarg(SELF_PHAR_NAME).' '.escapeshellarg('service/accetta_richieste.php').' '.escapeshellarg(json_encode(array($username, $password, $user['accetta_richieste'], $user['proxy']))));
        end($processes)->start($loop); //muove puntatore all'ultimo processo e lo mette nel esecuzione lista
        if(DEBUG_MODE){end($processes)->stdout->on('data',function($chunk){echo $chunk;});} //printa le cuose del processo
        if (strpos($user['accetta_richieste'], 'only_') === 0) { //se deve accettare solo tot richieste
          end($processes)->on('exit', function($exitCode, $termSignal) use($pdo, $user) { //quando finisce re-imposta su off accetta_richieste nel database
            echo "Processo accetta richieste limitate di $user[ig_username] terminato; disattivo accetta_richieste nel database...";
            $pdo->prepare('UPDATE ig_bot SET accetta_richieste=? WHERE user_id=?')->execute(['off', $user['user_id']]);
            echo "Fatto.\n";
          });
        }
        echo "Accetta richieste di $user[ig_username] avviato.\n";
      }
      if ($user['cancella_direct'] !== 'off' and $user['plan'] === 'premium') {
        $processes[] = new React\ChildProcess\Process('exec php '.escapeshellarg(SELF_PHAR_NAME).' '.escapeshellarg('service/cancella_direct.php').' '.escapeshellarg(json_encode(array($username, $password, $user['cancella_direct'], $user['proxy']))));
        end($processes)->start($loop); //muove puntatore all'ultimo processo e lo mette nel esecuzione lista
        if(DEBUG_MODE){end($processes)->stdout->on('data',function($chunk){echo $chunk;});} //printa le cuose del processo
        echo "Cancella direct di $user[ig_username] avviato.\n";
      }
      if ($user['cancella_seguiti'] !== 'off' and $user['plan'] === 'premium') {
        $processes[] = new React\ChildProcess\Process('exec php '.escapeshellarg(SELF_PHAR_NAME).' '.escapeshellarg('service/cancella_seguiti.php').' '.escapeshellarg(json_encode(array($username, $password, $user['cancella_seguiti'], $user['proxy']))));
        end($processes)->start($loop); //muove puntatore all'ultimo processo e lo mette nel esecuzione lista
        end($processes)->on('exit', function($exitCode, $termSignal) use($pdo, $user) { //quando finisce re-imposta su off cancella_seguiti nel database
          echo "Processo cancella seguiti di $user[ig_username] terminato; disattivo cancella_seguiti nel database...";
          $pdo->prepare('UPDATE ig_bot SET cancella_seguiti=? WHERE user_id=?')->execute(['off', $user['user_id']]);
          echo "Fatto.\n";
        });
        if(DEBUG_MODE){end($processes)->stdout->on('data',function($chunk){echo $chunk;});} //printa le cuose del processo
        echo "Cancella seguiti di $user[ig_username] avviato.\n";
      }
      if ($user['archivia_feed'] !== 'off' and $user['plan'] === 'premium') {
        $processes[] = new React\ChildProcess\Process('exec php '.escapeshellarg(SELF_PHAR_NAME).' '.escapeshellarg('service/archivia_feed.php').' '.escapeshellarg(json_encode(array($username, $password, $user['archivia_feed'], $user['proxy']))));
        end($processes)->start($loop); //muove puntatore all'ultimo processo e lo mette nel esecuzione lista
        if(DEBUG_MODE){end($processes)->stdout->on('data',function($chunk){echo $chunk;});} //printa le cuose del processo
        end($processes)->on('exit', function($exitCode, $termSignal) use($pdo, $user) { //quando finisce re-imposta su off accetta_richieste nel database
          echo "Processo archivia feed di $user[ig_username] terminato; disattivo archivia_feed nel database...";
          $pdo->prepare('UPDATE ig_bot SET archivia_feed=? WHERE user_id=?')->execute(['off', $user['user_id']]);
          echo "Fatto.\n";
        });
        echo "Archivia feed di $user[ig_username] avviato.\n";
      }
      if ($user['cancella_post'] !== 'off' and $user['plan'] === 'premium') {
        $processes[] = new React\ChildProcess\Process('exec php '.escapeshellarg(SELF_PHAR_NAME).' '.escapeshellarg('service/cancella_post.php').' '.escapeshellarg(json_encode(array($username, $password, $user['cancella_post'], $user['proxy']))));
        end($processes)->start($loop); //muove puntatore all'ultimo processo e lo mette nel esecuzione lista
        if(DEBUG_MODE){end($processes)->stdout->on('data',function($chunk){echo $chunk;});} //printa le cuose del processo
        echo "Cancella post di $user[ig_username] avviato.\n";
      }
      if ($user['ai_follow'] !== 'off' and $user['plan'] === 'premium') {
        $processes[] = new React\ChildProcess\Process('cd '.escapeshellarg(DIR . '/ig_sessions') . ' && python3 -m instabot_py --ignore-updates --login ' . escapeshellarg($username) . ' --password ' . escapeshellarg($password));
        end($processes)->start($loop); //muove puntatore all'ultimo processo e lo mette nel esecuzione lista
        if(DEBUG_MODE){end($processes)->stdout->on('data',function($chunk){echo $chunk;});} //printa le cuose del processo
        echo "AI follow di $user[ig_username] avviato.\n";
      }
    }
  }
  reset($processes); //muove puntatore array al u inizio

  $loop->addPeriodicTimer(2, function () use($processes, $result, $pdo) {
    $resultNew = $pdo->prepare('SELECT * FROM ig_bot');
    $resultNew->execute();
    $resultNew = $resultNew->fetchAll();
    if ($resultNew !== $result) {
      echo "Il database è stato aggiornato.\n";
      echo "Kill degli script avviato...";
      $n = 0;
      foreach ($processes as $process) {
        $process->terminate();
        $n++;
        echo ' '.$n;
      }
      echo "\nProcessi killati,, riavviamento di tutto . . .\n";
      run();
    }
  });

  $loop->run();
}
run();
