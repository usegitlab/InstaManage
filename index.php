<?php
echo 'Bot Telegram avviato.';
date_default_timezone_set('Europe/Rome');
setlocale(LC_ALL, 'it_IT.UTF-8');
require __DIR__.'/EzTG.php';
define('ONLY_PROXY', false); //permette di usare il bot solo se si imposta un proxy
//cose instagram
//$GLOBAL_INSTAGRAM = NULL;
require __DIR__.'/vendor/autoload.php';
use InstagramAPI\Exception\ChallengeRequiredException;
use InstagramAPI\Instagram;
use InstagramAPI\Response\LoginResponse;
class ExtendedInstagram extends Instagram {
  public function changeUser($username, $password) {
    $this->_setUser($username, $password);
  }
}
function rrmdir($dir) {
  if (is_dir($dir)) {
    $objects = scandir($dir);
    foreach ($objects as $object) {
      if ($object != "." && $object != "..") {
        if (filetype($dir."/".$object) == "dir")
        rrmdir($dir."/".$object);
        else unlink   ($dir."/".$object);
      }
    }
    reset($objects);
    rmdir($dir);
  }
}
//fine cacate
$callback = function($u, $t) {
  global $GLOBAL_INSTAGRAM;
  if (isset($u['callback_query'])) {
    $u['message'] = $u['callback_query']['message'];
    $u['message']['from'] = $u['callback_query']['from'];
    $cbdata = $u['callback_query']['data'];
    $cbid = $u['callback_query']['id'];
  } else {
    $cbdata = NULL;
    $cbid = NULL;
  }
  if (isset($u['message']['chat']['id'])) $chatID = $u['message']['chat']['id']; else $chatID = NULL;
  if (isset($u['message']['from']['id'])) $userID = $u['message']['from']['id']; else $userID = NULL;
  if (isset($u['message']['text'])) $msg = $u['message']['text']; else $msg = NULL;
  if (isset($u['message']['message_id'])) $msgid = $u['message']['message_id']; else $msgid = NULL;
  if (isset($u['message']['from']['username'])) $username = $u['message']['from']['username']; else $username = NULL;
  if (isset($u['message']['from']['first_name'])) $name = $u['message']['from']['first_name']; else $name = NULL;
  if (isset($u['message']['chat']['type'])) $type = $u['message']['chat']['type']; else $type = NULL;
  if (isset($u['message']['chat']['type']) and $u['message']['chat']['type'] !== 'private') return true; //blocco gruppi
  if ($cbdata) $msg = ''; //messaggio diverso da cbdata
  require __DIR__.'/admin_check.php';
};
$EzTG = new EzTG(array('token' => TELEGRAM_BOT_TOKEN, 'callback' => $callback, 'throw_telegram_errors' => false, 'objects' => false));
