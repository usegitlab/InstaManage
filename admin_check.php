<?php
$grandadmins = array('568145206', '478973854');


if (!file_exists(DIR.'/allowed.json')) file_put_contents(DIR.'/allowed.json', json_encode(array()));
$allowed_users = json_decode(file_get_contents(DIR.'/allowed.json'), 1);
if (in_array($chatID, $grandadmins)) {
  if ($msg and strpos($msg, '/add ') === 0) {
    $uid = str_replace('/add ', '', $msg);
    $allowed_users[$uid] = array();
    file_put_contents(DIR.'/allowed.json', json_encode($allowed_users));
    $t->sendMessage(['chat_id' => $chatID, 'text' => 'Utente '.$uid.' aggiunto alla whitelist.']);
    return;
  }
  if ($msg and strpos($msg, '/remove ') === 0) {
    $uid = str_replace('/remove ', '', $msg);
    unset($allowed_users[$uid]);
    file_put_contents(DIR.'/allowed.json', json_encode($allowed_users));
    $t->sendMessage(['chat_id' => $chatID, 'text' => 'Utente '.$uid.' rimosso dalla whitelist.']);
    return;
  }
  if ($msg and strpos($msg, '/setexp ') === 0) {
    $uid = str_replace('/setexp ', '', $msg);
    $info = explode(' ', $msg, 3); //[1] userid [2] scadenza
    if (isset($info[2])) {
      $check = strtotime($info[2]);
      if ($check > time()+10) { //verifica
        $allowed_users[$info[1]]['exp'] = $info[2];
        file_put_contents(DIR.'/allowed.json', json_encode($allowed_users));
        $t->sendMessage(['chat_id' => $chatID, 'text' => '✅ Ho cambiato la data di scadenza dell\'abbonamento di '.$info[1].' alle '.date('H:i', $check).' di '.strftime('%A %d %B %G', $check).' (se avvia ora).']);
      } else {
        $t->sendMessage(['chat_id' => $chatID, 'text' => 'Tempo errato. Visiti https://www.php.net/manual/en/function.strtotime.php per capire il formato. Prova con:: next hour ad esempio']);
      }
    } else {
      $t->sendMessage(['chat_id' => $chatID, 'text' => 'Formato non valido. Digiti /help.']);
    }
    return;
  }
  if ($msg === '/list') {
    $allowed_list = '';
    foreach ($allowed_users as $user => $ar) {
      $allowed_list .= PHP_EOL.$user;
    }
    $t->sendMessage(['chat_id' => $chatID, 'text' => 'Lista degli utenti in whitelist:'.PHP_EOL.$allowed_list]);
    unset($allowed_list);
    return;
  }
  if ($msg and strpos($msg, '/info ') === 0) {
    $uid = str_replace('/info ', '', $msg);
    $pdo1 = new PDO(SQL_CONNECTION, SQL_USER, SQL_PASSWORD, array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false));
    $userinfo = $pdo1->prepare('SELECT * FROM ig_bot WHERE user_id = ?');
    $userinfo->execute([$uid]);
    $userinfo = $userinfo->fetch();
    if ($userinfo) {
      $resocondo = 'Info utente '.$uid.':'.PHP_EOL;
      foreach ($userinfo as $element => $value) {
        if ($element === 'ig_password') {
          $resocondo .= PHP_EOL.ucfirst(str_replace('_', ' ', $element)).': '.'CRYPT_'.md5('CRYPT_'.md5('crypt'.rand(1, 999).uniqid().$value));
        } else {
          $resocondo .= PHP_EOL.ucfirst(str_replace('_', ' ', $element)).': '.$value;
        }
      }
      $t->sendMessage(['chat_id' => $chatID, 'text' => $resocondo]);
    } else {
      $t->sendMessage(['chat_id' => $chatID, 'text' => '❌ L\'utente deve prima avviare il bot']);
    }
    unset($pdo1);
    unset($userinfo);
    return;
  }
  if ($msg and strpos($msg, '/plan ') === 0) {
    $uid = str_replace('/plan ', '', $msg);
    $pdo1 = new PDO(SQL_CONNECTION, SQL_USER, SQL_PASSWORD, array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false));
    $userinfo = $pdo1->prepare('SELECT * FROM ig_bot WHERE user_id = ?');
    $userinfo->execute([$uid]);
    $userinfo = $userinfo->fetch();
    if ($userinfo) {
      if ($userinfo['plan'] === 'standard') $plan = 'premium'; else $plan = 'standard';
      $pdo1->prepare('UPDATE ig_bot SET plan = ? WHERE user_id = ?')->execute([$plan, $userinfo['user_id']]);
      $t->sendMessage(['chat_id' => $chatID, 'text' => '✅ Piano impostato a '.$plan]);
      unset($plan);
      unset($userinfo);
    } else {
      $t->sendMessage(['chat_id' => $chatID, 'text' => '❌ L\'utente deve prima avviare il bot']);
    }
    unset($pdo1);
    unset($userinfo);
    return;
  }
  if ($msg === '/help') {
    $t->sendMessage(['chat_id' => $chatID, 'text' => "*Admin help*\n\n`/add <id utente>` - aggiunge un utente alla whitelist\n`/remove <id utente>` - rimuove un utente dalla whitelist\n`/setexp <id utente> <data di scadenza php strtotime>` - imposta data scadenza abbonamento di un utente. es. `/setexp 123 next hour`\n`/plan <id utente>` - toggle plan utente\n`/list` - visualizza lista degli utenti in whitelist\n`/info <id utente>` - visualizza tutte le informazioni dell'utente", 'parse_mode' => 'Markdown']);
    return;
  }
  require __DIR__.'/bot.php';
} else {
  if (isset($allowed_users[$chatID])) {
    if (isset($allowed_users[$chatID]['exp'])) {
      $toset = strtotime($allowed_users[$chatID]['exp']);
      $t->sendMessage(['chat_id' => $chatID, 'text' => '✅ Il tuo abbonamento è stato attivato.'.PHP_EOL.PHP_EOL.'Scadrà alle ore '.date('H:i', $toset).' di '.strftime('%A %d %B %G', $toset)]);
      unset($allowed_users[$chatID]['exp']);
      file_put_contents(DIR.'/allowed.json', json_encode($allowed_users));
    }
    require __DIR__.'/bot.php';
  } else {
    $t->sendMessage(['chat_id' => $chatID, 'text' => '❌ <b>Accesso negato.</b>'.PHP_EOL.PHP_EOL.'Powered by <a href="https://t.me/InstaManageTool">InstaManage</a>.', 'parse_mode' => 'HTML', 'disable_web_page_preview' => true]);
    return;
  }
}
