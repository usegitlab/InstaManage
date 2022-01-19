<?php
define('INSTAMANGE_VERSION', '0.9.1');
if (!Phar::running()) {
  die('ERR_NO_PHAR'.PHP_EOL);
} else {
  define('DIR', str_replace('phar://', '', pathinfo(__DIR__, PATHINFO_DIRNAME)));
  define('SELF_PHAR_NAME', DIR . '/' . basename($_SERVER['SCRIPT_NAME']));
}
if (!file_exists(DIR . '/conf.json')) {
  $conf = ['key' => '*instamange key*', 'telegram_bot_token' => '*token*', 'sql_connection' => 'mysql:host=localhost;dbname=bot;charset=utf8mb4', 'sql_user' => 'root', 'sql_password' => 'instagram'];
  file_put_contents(DIR . '/conf.json', json_encode($conf, JSON_PRETTY_PRINT));
  die('File di configurazione creato'.PHP_EOL);
} else {
  $conf = json_decode(file_get_contents(DIR . '/conf.json'), 1);
  if (isset($conf['key']) and isset($conf['telegram_bot_token']) and isset($conf['sql_connection']) and isset($conf['sql_user']) and isset($conf['sql_password'])) {
    define('TELEGRAM_BOT_TOKEN', $conf['telegram_bot_token']);
    define('SQL_CONNECTION', $conf['sql_connection']);
    define('SQL_USER', $conf['sql_user']);
    define('SQL_PASSWORD', $conf['sql_password']);
    $verify = @file_get_contents('https://lic.mrbonus.it/instamange.php?key='.urlencode($conf['key']));
    $verify = json_decode($verify, 1);
    if (isset($verify['status']) and $verify['status']) {
      if (!file_exists(DIR.'/ig_sessions')) {
        mkdir(DIR.'/ig_sessions');
      }
      if (isset($argv[1]) and $argv[1]) { //includere altri file
        $filetoinclude = $argv[1];
        unset($argv[1]);
        sort($argv);
        require __DIR__ . '/' . $filetoinclude;
      } else { //avvio bot
        $banner = 'InstaManage v.' . INSTAMANGE_VERSION;
        $bannerl = str_repeat('=', strlen($banner));
        echo $bannerl . PHP_EOL . $banner . PHP_EOL . $bannerl;
        sleep(2);
        echo PHP_EOL;
        passthru('screen -d -m php '.escapeshellarg(SELF_PHAR_NAME).' index.php');
        passthru('screen -d -m php '.escapeshellarg(SELF_PHAR_NAME).' service/service.php');
        echo 'InstaManage è stato avviato.'.PHP_EOL;
      }
    } else {
      die('Chiave non valida.'.PHP_EOL);
    }
  } else {
    die('File di configurazione errato.'.PHP_EOL);
  }
}
