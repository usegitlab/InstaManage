<?php
use InstagramAPI\Exception\ChallengeRequiredException;
use InstagramAPI\Instagram;
use InstagramAPI\Response\LoginResponse;
$username = $user['ig_username'];
$password = $user['ig_password'];
$verification_method = 1; //0 = SMS, 1 = Email

//Leave these
$user_id = '';
$challenge_id = '';


$instagram = new ExtendedInstagram(1, 0, ['storage' => 'file', 'basefolder' => DIR.'/ig_sessions/']);
if ($user['proxy'] !== 'off') $instagram->setProxy($user['proxy']);
try {
  $loginResponse = $instagram->login($username, $password);
  //$user_id = $instagram->account_id;
  if (!is_null($loginResponse) && $loginResponse->isTwoFactorRequired()) {
    $twoFactorIdentifier = $loginResponse->getTwoFactorInfo()->getTwoFactorIdentifier();
    $keyboard = $t->newKeyboard('inline')
    ->add('🔐 Usa codice di recupero', 'use_backup_code')
    ->done();
    $status('two_factor__'.json_encode(['id' => $twoFactorIdentifier, 'method' => 1]));
    $reply('Invia il codice della verifica in due passaggi.', $keyboard);
    return;
  }
  $keyboard = $t->newKeyboard('inline')
  ->add('🔙 Vai alla home', 'start')
  ->done();
  $reply('✅ Login eseguito.', $keyboard);
  $status();
  return;
} catch (Exception $exception) {
  if (!method_exists($exception, 'getResponse')) {
    throw new Exception($exception->getMessage());
    return;
  }
  $response = $exception->getResponse();
  if ($exception instanceof ChallengeRequiredException) {
    sleep(5);
    $customResponse = $instagram->request(substr($response->getChallenge()->getApiPath(), 1))->setNeedsAuth(false)->addPost('choice', $verification_method)->getDecodedResponse();
    if (is_array($customResponse)) {
      $user_id = $customResponse['user_id'];
      $challenge_id = $customResponse['nonce_code'];
      $status('challenge_code;'.json_encode(array($user_id, $challenge_id)));
      $reply('Invia il codice che hai ricevuto tramite '.($verification_method ? 'email' : 'sms').':' );
      //$GLOBAL_INSTAGRAM = $instagram;
      return;
    } else {
      throw new Exception('Weird response from challenge request: '.json_encode($custom_response));
      return;
    }
  } else {
    throw new Exception($exception->getMessage());
  }
}
