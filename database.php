<?php
$pdo = new PDO(SQL_CONNECTION, SQL_USER, SQL_PASSWORD, array(PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false));
$result = $pdo->prepare('SELECT * FROM ig_bot WHERE user_id = ?');
$result->execute([$userID]);
$user = $result->fetch();
if (empty($user)) {
  $user = ['user_id' => $userID, 'name' => $name, 'username' => $username, 'status' => '', 'ig_username' => '', 'ig_password' => '', 'accetta_richieste' => 'off', 'cancella_direct' => 'off', 'cancella_seguiti' => 'off', 'cancella_storie' => 'off', 'cancella_post' => 'off', 'archivia_feed' => 'off', 'ai_follow' => 'off', 'proxy' => 'off', 'expdate' => 'unlimited', 'plan' => 'standard'];
  $pdo->prepare('INSERT INTO ig_bot (user_id, name, username, status, ig_username, ig_password, accetta_richieste, cancella_direct, cancella_seguiti, cancella_storie, cancella_post, proxy, expdate, archivia_feed, plan, ai_follow) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)')->execute(array($userID, $name, $username, '', '', '', 'off', 'off', 'off', 'off', 'off', 'off', 'unlimited', 'off', 'standard', 'off'));
}
if ($name !== $user['name'] or $username !== $user['name']) {
  $pdo->prepare('UPDATE ig_bot SET name=?, username=? WHERE user_id=?')->execute([$name, $username, $userID]);
}

if (isset($toset)) { //gestione abbonamento
  $pdo->prepare('UPDATE ig_bot SET expdate=? WHERE user_id=?')->execute([$toset, $userID]);
  $user['expdate'] = $toset;
  unset($toset);
}
