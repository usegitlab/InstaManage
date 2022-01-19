<?php
use InstagramAPI\Exception\ChallengeRequiredException;
use InstagramAPI\Instagram;
use InstagramAPI\Response\LoginResponse;
$username = $user['ig_username'];
$password = $user['ig_password'];
$code = (string) trim($msg);
$verification_method = 1; //0 = SMS, 1 = Email
/*
class ExtendedInstagram extends Instagram {
  public function changeUser($username, $password) {
    $this->_setUser($username, $password);
  }
*/
//$instagram = $GLOBAL_INSTAGRAM;
$instagram = new ExtendedInstagram(1, 0, ['storage' => 'file', 'basefolder' => DIR.'/ig_sessions/']);
if ($user['proxy'] !== 'off') $instagram->setProxy($user['proxy']);
try {
  $instagram->login($username, $password);
} catch (Exception $e) { }
$instagram->changeUser($username, $password);
echo "INFOS:\n\nUsername:$username\nPassword: $password\nCode: $code\nUser_id: $user_id\nChallenge_id:$challenge_id\n\nVamos. . . \n";
$customResponse = $instagram->request("challenge/$user_id/$challenge_id/")->setNeedsAuth(false)->addPost('security_code', $code)->getDecodedResponse();
var_dump($customResponse);
if (!is_array($customResponse)) throw new Exception('Weird response from challenge request: '.json_encode($customResponse));

if ($customResponse['status'] === 'ok' && (int) $customResponse['logged_in_user']['pk'] === (int)$user_id) {
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Vai alla home', 'start')
  ->done();
  $reply('✅ Login eseguito.', $keyboard);
  $status();
  return;
} else {
  throw new Exception('Response '.json_encode($customResponse));
}
