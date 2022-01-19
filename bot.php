<?php
/*
OBSOLTE CODE BY PAOLO WU AND PEPPELG1

THE CODE NOT WORK MORE!!

1-43 cuose importants x robot
44-69 menu start
72-108 cambia account
111-196 + do_login.php e challenge_code.php login
199-246 cancella archivio
249-294 imposta richieste
297-315 cancella direct
318-336 cancella seguiti
339-446 proxy
448-495 autoposting
497-551 archivia feed






*/
require __DIR__.'/database.php';
if (isset($user['expdate']) and is_numeric($user['expdate']) and time() > $user['expdate']) { //abbonamento scaduto
  $t->sendMessage(['chat_id' => $chatID, 'text' => '❌ <b>Il tuo abbonamento è scaduto alle ore '.date('H:i', $user['expdate']).' di '.strftime('%A %d %B %G', $user['expdate']).'</b>'.PHP_EOL.PHP_EOL.'Powered by <a href="https://t.me/InstaManageTool">InstaManage</a>.', 'parse_mode' => 'HTML', 'disable_web_page_preview' => true]);
  return;
}
$status = function($status='') use($pdo, $chatID) {
  $pdo->prepare('UPDATE ig_bot SET status = ? WHERE user_id = ?')->execute([$status, $chatID]);
};
$reply = function($msg, $keyboard=NULL, $cquery='', $parse_mode=NULL) use($t, $chatID, $msgid, $u, $cbdata, $cbid) {
  if ($cbdata) {
    $t->answerCallbackQuery(['callback_query_id' => $cbid, 'text' => $cquery]);
    $t->editMessageText(['chat_id' => $chatID, 'message_id' => $msgid, 'text' => $msg, 'reply_markup' => $keyboard, 'parse_mode' => $parse_mode]);
  } else {
    $t->sendMessage(['chat_id' => $chatID, 'text' => $msg, 'reply_markup' => $keyboard, 'parse_mode' => $parse_mode]);
  }
};
if (ONLY_PROXY and $user['proxy'] === 'off' and strpos($cbdata, 'proxy') === false and strpos($user['status'], 'proxy') === false) { //se ONLY_PROXY è su on diventa obbligatorio impostare un proxy
  $keyboard = $t->newKeyboard('inline')
  ->add('Imposta proxy', 'proxy')
  ->done();
  $reply('❗️ Per usare il bot, devi prima impostare un proxy.', $keyboard);
  return;
}
if ($msg === '/start' or $cbdata === 'start') {
  if (!$user['ig_username'] or !$user['ig_password']) $cbdata = 'change_password'; //se non è stato aggiunto u account lo manda al coso per aggiungerlo
  elseif ($user['status'] === 'login') $cbdata = 'login';
  else {
    $status();
    $resocondo = '';
    foreach ($user as $element => $value) {
      if (!in_array($element, array('id', 'user_id', 'name', 'username', 'status', 'ig_username', 'ig_password', 'expdate'))) {
        $resocondo .= PHP_EOL.ucfirst(str_replace('_', ' ', $element)).': '.$value;
      }
    }
    $keyboard = $t->newKeyboard('inline')
    ->add('👤 Cambia utente', 'change_password')
    ->newLine()
    ->add('🖼 Cancella archivio', 'cancella_archivio')->add('👨‍👨‍👦‍👦 Accetta richieste', 'richieste')
    ->newLine()
    ->add('✈️Cancella direct', 'cancella_direct')->add('👬 Cancella seguiti', 'cancella_seguiti')
    ->newLine()
    ->add('🏞 Archivia self feed', 'archivia_feed')//->add('Autoposting', 'autoposting')
    ->newLine()
    ->add('🧠 AI follow', 'ai_follow')
    ->newLine()
    ->add('🌎 Imposta proxy', 'proxy')
    ->done();
    $reply('Utente attuale: '.$user['ig_username'].$resocondo, $keyboard);
    return;
  }
}

//Sezione cambia password
if ($cbdata === 'change_password') {
  $keyboard = $t->newKeyboard('inline')
  ->add('🚫 Annulla', 'start')->add('Imposta proxy', 'proxy')
  ->done();
  $reply('Invia l\'username dell\'account.', $keyboard);
  $status('send_username');
  return;
}
if ($msg and $user['status'] === 'send_username') {
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'start')
  ->done();
  $reply('Invia la password dell\'account.', $keyboard);
  $status('send_password');
  $pdo->prepare('UPDATE ig_bot SET ig_username = ?, ig_password = ? WHERE user_id = ?')->execute([$msg, '', $chatID]);
  return;
}
if ($msg and $user['status'] === 'send_password') {
  $keyboard = $t->newKeyboard('inline')
  ->add('🔑 Login', 'login')
  ->done();
  $reply('👤 Username: '.$user['ig_username'].PHP_EOL.'🔑 Password: '.$msg, $keyboard);
  $status();
  $pdo->prepare('UPDATE ig_bot SET ig_password = ? WHERE user_id = ?')->execute([$msg, $chatID]);
  if ($user['ig_username'] and $user['ig_password']) { //famo logout
    try {
      $ig = new \InstagramAPI\Instagram(1, 0, ['storage' => 'file', 'basefolder' => DIR.'/ig_sessions/']);
      if ($user['proxy'] !== 'off') $ig->setProxy($user['proxy']);
      $ig->login($user['ig_username'], $user['ig_password']);
      $ig->logout();
    } catch (Exception $e) {
      //nun ci fa niende
    }
    rrmdir(DIR.'/ig_sessions/'.$status['ig_username']);
  }
  return;
}

//Sezione login e verifica account
if ($cbdata === 'login') {
  $reply('Login in corso...');
  try {
    //require __DIR__.'/vendor/autoload.php'; ora lo fa in index.php
    /*
    $ig = new \InstagramAPI\Instagram();
    $loginResponse = $ig->login($user['ig_username'], $user['ig_password']);
    if (!is_null($loginResponse) && $loginResponse->isTwoFactorRequired()) {
      $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
      $status('two_factor__'.$twoFactorIdentifier);
      $reply('Invia il codice della verifica in due passaggi.');
      return;
    }
    */
    require __DIR__.'/do_login.php';
  } catch (Exception $e) {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'start')
    ->done();
    $status();
    $pdo->prepare('UPDATE ig_bot SET ig_username = ?, ig_password = ? WHERE user_id = ?')->execute(['', '', $chatID]);
    $reply('❌ Si è verificato un errore: '.$e->getMessage(), $keyboard);
    return;
  }
}
if ($cbdata === 'use_backup_code') {
  $twoFaInfo = json_decode(trim(str_replace('two_factor__', '', $user['status'])), 1);
  $twoFaInfo['method'] = 2;
  $status('two_factor__'.json_encode($twoFaInfo));
  $reply('Invia il codice di recupero.');
  return;
}
if ($msg and strpos($user['status'], 'two_factor__') === 0) {
  try {
    //require __DIR__.'/vendor/autoload.php'; ora lo fa in index.php
    $twoFaInfo = json_decode(trim(str_replace('two_factor__', '', $user['status'])), 1);
    $twoFactorIdentifier = $twoFaInfo['id'];
    $userCode = trim($msg);
    if ($twoFaInfo['method'] === 2) {
      $userCode = str_replace(' ', '', $userCode);
      if (is_numeric($userCode) and strlen($userCode) === 8) {
        $userCode = chunk_split($userCode, 4, ' ');
      } else {
        $keyboard = $t->newKeyboard('inline')
        ->add('🚫 Annulla', 'start')
        ->done();
        $reply('🚫 Codice di recupero non valido.', $keyboard);
        return;
      }
    }
    $ig = new \InstagramAPI\Instagram(1, 0, ['storage' => 'file', 'basefolder' => DIR.'/ig_sessions/']);
    if ($user['proxy'] !== 'off') $ig->setProxy($user['proxy']);
    $ig->login($user['ig_username'], $user['ig_password']);
    $ig->finishTwoFactorLogin($user['ig_username'], $user['ig_password'], $twoFactorIdentifier, $userCode, (string) $twoFaInfo['method']);
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Vai alla home', 'start')
    ->done();
    $reply('✅ Login eseguito.', $keyboard);
    return;
  } catch (Exception $e) {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'start')
    ->done();
    $status();
    $pdo->prepare('UPDATE ig_bot SET ig_username = ?, ig_password = ? WHERE user_id = ?')->execute(['', '', $chatID]);
    $reply('❌ Si è verificato un errore: '.$e->getMessage(), $keyboard);
    return;
  }
}
if ($msg and strpos($user['status'], 'challenge_code;') === 0) {
  try {
    $vars = json_decode(str_replace('challenge_code;', '', $user['status']), true);
    $user_id = trim($vars[0]);
    $challenge_id = trim($vars[1]);
    //require __DIR__.'/vendor/autoload.php'; ora lo fa in index.php
    require __DIR__.'/challenge_code.php';
  } catch (Exception $e) {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'start')
    ->done();
    $status();
    $pdo->prepare('UPDATE ig_bot SET ig_username = ?, ig_password = ? WHERE user_id = ?')->execute(['', '', $chatID]);
    $reply('❌ Si è verificato un errore: '.$e->getMessage(), $keyboard);
    return;
  }
}

//Sezione cancella archivio
if ($cbdata === 'cancella_archivio' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('Storie', 'cancella_storie')
  ->add('Post', 'cancella_post')
  ->newLine()
  ->add('🔙 Torna indietro', 'start')
  ->done();
  $reply('Quale archivio desideri cancellare?', $keyboard);
  return;
}
if ($cbdata === 'cancella_storie' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('✅ On', 'set_storie_on')->add('❌ Off', 'set_storie_off')
  ->newLine()
  ->add('🔙 Torna indietro', 'cancella_archivio')
  ->done();
  $reply('Cancellare le storie?'.PHP_EOL.PHP_EOL.'Valore attuale: '.$user['cancella_storie'], $keyboard);
  return;
}
if ($cbdata and strpos($cbdata, 'set_storie_') === 0 and $user['plan'] === 'premium') {
  $value = str_replace('set_storie_', '', $cbdata);
  if (!in_array($value, array('on', 'off'))) return; //valori consentiti
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'cancella_storie')
  ->done();
  $reply('✅ Valore impostato a '.$value, $keyboard);
  $pdo->prepare('UPDATE ig_bot SET cancella_storie = ? WHERE user_id = ?')->execute([$value, $chatID]);
  return;
}
if ($cbdata === 'cancella_post' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('✅ On', 'set_post_on')->add('❌ Off', 'set_post_off')
  ->newLine()
  ->add('🔙 Torna indietro', 'cancella_archivio')
  ->done();
  $reply('Cancellare i post?'.PHP_EOL.PHP_EOL.'Valore attuale: '.$user['cancella_post'], $keyboard);
  return;
}
if ($cbdata and strpos($cbdata, 'set_post_') === 0 and $user['plan'] === 'premium') {
  $value = str_replace('set_post_', '', $cbdata);
  if (!in_array($value, array('on', 'off'))) return; //valori consentiti
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'cancella_post')
  ->done();
  $reply('✅ Valore impostato a '.$value, $keyboard);
  $pdo->prepare('UPDATE ig_bot SET cancella_post = ? WHERE user_id = ?')->execute([$value, $chatID]);
  return;
}

//Sezione accetta richieste
if ($cbdata === 'richieste') {
  $keyboard = $t->newKeyboard('inline')
  ->add('Random', 'set_richieste_random')->add('1', 'set_richieste_1')->add('2', 'set_richieste_2')->add('3', 'set_richieste_3')
  ->newLine()
  ->add('Super accept📛', 'set_richieste_super')->add('Personalizzato', 'richieste_personalizzato')->add('❌ Off', 'set_richieste_off')
  ->newLine()
  ->add('🔙 Torna indietro', 'start')
  ->done();
  $reply('Seleziona la velocità con cui accettare le richieste. Personalizzato serve ad accettare un numero limitato di richieste.'.PHP_EOL.PHP_EOL.'Valore attuale: '.$user['accetta_richieste'], $keyboard);
  return;
}
if ($cbdata and strpos($cbdata, 'set_richieste_') === 0) {
  $value = str_replace('set_richieste_', '', $cbdata);
  if (!in_array($value, array('random', '1', '2', '3', 'super', 'off'))) return; //valori consentiti
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'richieste')
  ->done();
  $reply('✅ Valore impostato a '.$value, $keyboard);
  $pdo->prepare('UPDATE ig_bot SET accetta_richieste = ? WHERE user_id = ?')->execute([$value, $chatID]);
  return;
}
if ($cbdata === 'richieste_personalizzato') {
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'richieste')
  ->done();
  $reply('Invia il numero di richieste da accettare una sola volta.', $keyboard);
  $status('richieste_personalizzato');
  return;
}
if ($msg and $user['status'] === 'richieste_personalizzato') {
  if (is_numeric($msg)) {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'richieste')
    ->done();
    $reply('✅ Verranno accettate '.$msg.' richieste.', $keyboard);
    $status();
    $pdo->prepare('UPDATE ig_bot SET accetta_richieste = ? WHERE user_id = ?')->execute(['only_'.$msg, $chatID]);
    return;
  } else {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'richieste')
    ->done();
    $reply('⚠️ Devi inviare un numero.', $keyboard);
    return;
  }
}

//Sezione cancella direct
if ($cbdata === 'cancella_direct' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('✅ On', 'set_direct_on')->add('❌ Off', 'set_direct_off')
  ->newLine()
  ->add('🔙 Torna indietro', 'start')
  ->done();
  $reply('Cancellare i direct?'.PHP_EOL.PHP_EOL.'Valore attuale: '.$user['cancella_direct'], $keyboard);
  return;
}
if ($cbdata and strpos($cbdata, 'set_direct_') === 0 and $user['plan'] === 'premium') {
  $value = str_replace('set_direct_', '', $cbdata);
  if (!in_array($value, array('on', 'off'))) return; //valori consentiti
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'cancella_direct')
  ->done();
  $reply('✅ Valore impostato a '.$value, $keyboard);
  $pdo->prepare('UPDATE ig_bot SET cancella_direct = ? WHERE user_id = ?')->execute([$value, $chatID]);
  return;
}

//Sezione cancella seguiti
if ($cbdata === 'cancella_seguiti' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('✅ On', 'set_seguiti_on')->add('❌ Off', 'set_seguiti_off')
  ->newLine()
  ->add('🔙 Torna indietro', 'start')
  ->done();
  $reply('Cancellare i seguiti?'.PHP_EOL.PHP_EOL.'Valore attuale: '.$user['cancella_seguiti'], $keyboard);
  return;
}
if ($cbdata and strpos($cbdata, 'set_seguiti_') === 0 and $user['plan'] === 'premium') {
  $value = str_replace('set_seguiti_', '', $cbdata);
  if (!in_array($value, array('on', 'off'))) return; //valori consentiti
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'cancella_seguiti')
  ->done();
  $reply('✅ Valore impostato a '.$value, $keyboard);
  $pdo->prepare('UPDATE ig_bot SET cancella_seguiti = ? WHERE user_id = ?')->execute([$value, $chatID]);
  return;
}

//Sezione proxy
if ($cbdata === 'proxy') {
  $status();
  $keyboard = $t->newKeyboard('inline')
  ->add('HTTP', 'set_proxy_http')->add('HTTPS', 'set_proxy_https')->add('SOCKS (tcp)', 'set_proxy_tcp')
  ->newLine()
  ->add('✨ Proxy gratuito', 'set_free_proxy')->add('❔ Verifica proxy', 'check_proxy')->add('❌ Disattiva', 'set_proxy_off')
  ->newLine()
  ->add('🔙 Torna indietro', 'start')
  ->done();
  $reply('Se hai un proxy, seleziona il tipo (HTTP, HTTPS, SOCKS) e impostalo. Se non hai un proxy, ne puoi usare uno gratuito (spesso non funziona o smette di funzionare dopo un po\' e ha una qualità scadente) selezionando "✨ Proxy gratuito". Puoi vedere se il proxy impostato funziona con "❔ Verifica proxy".'.PHP_EOL.PHP_EOL.'Proxy in uso: <code>'.$user['proxy'].'</code>', $keyboard, '', 'html');
  return;
}
if ($cbdata === 'set_free_proxy') {
  $reply('✨ Attendi...');
  $proxy = json_decode(file_get_contents('https://api.getproxylist.com/proxy?protocol=http&allowsCustomHeaders=1&allowsCookies=1&allowsPost=1&allowsHttps=1'), 1);
  if (isset($proxy['ip']) and isset($proxy['port'])) {
    $proxyip = $proxy['ip'];
    $proxyport = $proxy['port'];
  } else { //limite richieste raggiunto, parsing free proxy list
    $proxylistraw = file_get_contents('https://free-proxy-list.net');
    $proxylistraw = explode('<td>', $proxylistraw);
    unset($proxylistraw[0]);
    $proxyip = str_replace('</td>', '', $proxylistraw[1]);
    $proxyport = str_replace('</td>', '', $proxylistraw[2]);
  }
  if (is_numeric(str_replace(':', '', str_replace('.', '', $proxyip))) and is_numeric($proxyport)) {
    $keyboard = $t->newKeyboard('inline')
    ->add('❔ Verifica proxy', 'check_proxy')
    ->done();
    $pdo->prepare('UPDATE ig_bot SET proxy = ? WHERE user_id = ?')->execute(['http://'.$proxyip.':'.$proxyport, $chatID]);
    $reply("✅ Nuovo proxy impostato.\n\n$proxyip:$proxyport", $keyboard);
  } else {
    $reply('❌ Si è verificato un errore. Riprova più tardi.', $keyboard);
  }
  return;
}
if ($cbdata === 'check_proxy') {
  if ($user['proxy'] !== 'off') {
    $reply('⏳ Verifica in corso...');
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'proxy')
    ->done();
    try {
      $timestart = time();
      $client = new GuzzleHttp\Client(['base_uri' => 'https://instagram.com', 'timeout' => 15.0]);
      $response = $client->request('GET', '/', ['proxy' => $user['proxy']]);
      $timefinal = time()-$timestart;
      $mex = '✅ Il proxy sembra funzionare.';
      if ($timefinal>1) {
        $mex .= PHP_EOL.PHP_EOL.'👎 Il proxy ha un ritardo di '.$timefinal.' secondi, il bot sarà molto lento.';
      } else {
        $mex .= PHP_EOL.PHP_EOL.'👍 Il proxy ha un ritardo inferiore a un secondo.';
      }
      $reply($mex, $keyboard);
    } catch (Exception $e) {
      $reply('❌ Il proxy non funziona. <b>Torna indietro e impostane uno diverso, altrimenti il bot Instagram non potrà funzionare</b>.'.PHP_EOL.PHP_EOL.'Errore: <code>'.$e->getMessage().'</code>', $keyboard, '', 'HTML');
    }
  } else {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'proxy')
    ->done();
    $reply('❌ Nessun proxy impostato', $keyboard);
  }
  return;
}
if ($cbdata === 'set_proxy_off') {
  if (ONLY_PROXY) {
    $t->answerCallbackQuery(['callback_query_id' => $cbid, 'text' => '⚠️ Non è possibile disabilitare il proxy.', 'show_alert' => true]);
    return;
  }
  $pdo->prepare('UPDATE ig_bot SET proxy = ? WHERE user_id = ?')->execute(['off', $chatID]);
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'proxy')
  ->done();
  $reply('✅ Il proxy è stato disattivato.', $keyboard);
  return;
}
if ($cbdata and strpos($cbdata, 'set_proxy_') === 0) {
  $value = str_replace('set_proxy_', '', $cbdata);
  if (!in_array($value, array('http', 'https', 'tcp'))) return; //valori consentiti
  $status('set_proxy_'.$value);
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'proxy')
  ->done();
  $reply('Invia l\'ip del proxy. Usa il formato <code>ip:porta</code>. Se il proxy richiede autenticazione, usa il formato <code>username:password@ip:porta</code>', $keyboard, '', 'html');
  return;
}
if ($msg and strpos($user['status'], 'set_proxy_') === 0) {
  $msg1 = $msg;
  if (strpos($msg1, '@') !== false) $msg1 = explode('@', $msg1, 2)[1]; //rimuove la parte con le credenziali del proxy
  if (!is_numeric(str_replace(':', '', str_replace('.', '', $msg1))) or strpos($msg1, ':') === false) { //se l'ip non è valido
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'proxy')
    ->done();
    $reply('❌ Proxy non valido. Riprova', $keyboard);
    return;
  }
  $proxytype = str_replace('set_proxy_', '', $user['status']);
  $status();
  $pdo->prepare('UPDATE ig_bot SET proxy = ? WHERE user_id = ?')->execute([$proxytype.'://'.$msg, $chatID]);
  $keyboard = $t->newKeyboard('inline')
  ->add('❔ Verifica proxy', 'check_proxy')
  ->newLine()
  ->add('🔙 Torna indietro', 'proxy')
  ->done();
  $reply('✅ Proxy impostato.', $keyboard);
  return;
}

//Sezione autoposting
if ($cbdata === 'autoposting' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'start')
  ->done();
  $reply('Invia la data di invio del post nel formato <code>ORE:MINUTI GIORNO-MESE-ANNO</code> (es. 17:22 06/09/2019)', $keyboard, '', 'HTML');
  $status('auto_posting_send_date');
  return;
}
if ($msg and $user['status'] === 'auto_posting_send_date' and $user['plan'] === 'premium') {
  $parse = strtotime(str_replace('/', '-', $msg));
  if ($parse > time()) {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'autoposting')
    ->done();
    $reply('✅ Adesso invia la foto o il video da postare alle ore '.date('H:i', $parse).' di '.strftime('%A %d %B %G', $parse).PHP_EOL.'Per aggiungere la didascalia su Instagram, basta mettere la didascalia alla foto/video che devi inviare (qui su Telegram).', $keyboard);
    $status('autoposting_sendMedia_'.$parse);
  } else {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'start')
    ->done();
    $reply('❌ Formato non valido. Riprova.', $keyboard);
  }
  return;
}
if ((isset($u['message']['photo']) or isset($u['message']['video'])) and strpos($user['status'], 'autoposting_sendMedia_') === 0 and $user['plan'] === 'premium') {
  $time = str_replace('autoposting_sendMedia_', '', $user['status']);
  if (isset($u['message']['photo'])) {
    $type = 'photo';
    $file_id = array_values(array_slice($u['message']['photo'], -1))[0]['file_id'];
  }
  if (isset($u['message']['video'])) {
    $type = 'video';
    $file_id = $u['message']['video']['file_id'];
  }
  if (isset($u['message']['caption'])) $caption = $u['message']['caption']; else $caption = '';
  if (isset($t->getFile(['file_id' => $file_id])['file_path'])) {
    $pdo->prepare('INSERT INTO ig_bot_media (user_id, media_type, tg_media_id, caption, date) VALUES (?, ?, ?, ?, ?)')->execute([$chatID, $type, $file_id, $caption, $time]);
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'start')
    ->done();
    $reply('✅ Post programmato.', $keyboard);
    return;
  } else {
    $reply('❌ Impossibile salvare il file. Probabilmente è troppo grande. Riprova');
    return;
  }
}

//Sezione archivia feed
if ($cbdata === 'archivia_feed' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('Archivia tutto', 'set_archivia_feed_all')
  ->newLine()
  ->add('Low likes', 'archivia_feed_lowLikes')->add('Low views', 'archivia_feed_lowViews')
  ->newLine()
  ->add('❌ Off', 'set_archivia_feed_off')->add('🔙 Torna indietro', 'start')
  ->done();
  $reply("<b>Archivia feed</b>\n\n<i>Archivia tutto: archivia tutti i post che hai postato finora\nLow likes: archivia tutti i post più bassi di N likes\nLow views: archivia tutti i post più bassi di N views\nOff: disabilita archivia feed</i>\n\nStato: ".($user['archivia_feed'] !== 'off' ? 'on' : 'off'), $keyboard, '', 'HTML');
  $status();
  return;
}
if ($cbdata === 'set_archivia_feed_all' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'archivia_feed')
  ->done();
  $reply('✅ Verranno archiviati tutti i post che hai postato finora', $keyboard);
  $pdo->prepare('UPDATE ig_bot SET archivia_feed = ? WHERE user_id = ?')->execute(['all', $chatID]);
  return;
}
if (strpos($cbdata, 'archivia_feed_') === 0 and $user['plan'] === 'premium') {
  $type = str_replace('archivia_feed_', '', $cbdata);
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'archivia_feed')
  ->done();
  $reply('Invia il numero di '.strtolower(str_replace('low', '', $type)).' che deve avere un post per essere archiviato.', $keyboard);
  $status('set_'.$cbdata);
  return;
}
if ($msg and strpos($user['status'], 'set_archivia_feed_') === 0 and $user['plan'] === 'premium') {
  $type = str_replace('set_archivia_feed_', '', $user['status']);
  if (is_numeric($msg)) {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Torna indietro', 'archivia_feed')
    ->done();
    $reply('✅ Verranno archiviati tutti i post con '.strtolower(str_replace('low', '', $type)).' inferiori a '.$msg, $keyboard);
    $pdo->prepare('UPDATE ig_bot SET archivia_feed = ? WHERE user_id = ?')->execute([$type.';'.$msg, $chatID]);
    $status();
  } else {
    $keyboard = $t->newKeyboard('inline')
    ->add('🔙 Annulla', 'archivia_feed')
    ->done();
    $reply('❌ Devi inviare un numero', $keyboard);
  }
  return;
}
if ($cbdata === 'set_archivia_feed_off' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'archivia_feed')
  ->done();
  $reply('✅ Non verranno più archiviati i post', $keyboard);
  $pdo->prepare('UPDATE ig_bot SET archivia_feed = ? WHERE user_id = ?')->execute(['off', $chatID]);
  return;
}

//Sezione AI follow
if ($cbdata === 'ai_follow' and $user['plan'] === 'premium') {
  $keyboard = $t->newKeyboard('inline')
  ->add('✅ On', 'set_ai_follow_on')->add('❌ Off', 'set_ai_follow_off')
  ->newLine()
  ->add('🔙 Torna indietro', 'start')
  ->done();
  $reply('Abilitare AI follow?'.PHP_EOL.PHP_EOL.'Valore attuale: '.$user['ai_follow'], $keyboard);
  return;
}
if ($cbdata and strpos($cbdata, 'set_ai_follow_') === 0 and $user['plan'] === 'premium') {
  $value = str_replace('set_ai_follow_', '', $cbdata);
  if (!in_array($value, array('on', 'off'))) return; //valori consentiti
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Torna indietro', 'ai_follow')
  ->done();
  $reply('✅ Valore impostato a '.$value, $keyboard);
  $pdo->prepare('UPDATE ig_bot SET ai_follow = ? WHERE user_id = ?')->execute([$value, $chatID]);
  return;
}

//Se non viene fatto return
if ($cbdata) {
  $t->answerCallbackQuery(['callback_query_id' => $cbid, 'text' => '❌ Funzionalità non disponibile', 'show_alert' => true]); //non ha premium
  return;
}
