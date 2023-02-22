<?php

use OCP\Defaults;

$defaults = \OC::$server->query(Defaults::class);

$file = $_['file'];
$user = $_['user'];
$recipient = $_['recipient'];
$request_id = $_['request_id'];
$url = $_['url'];
$ios_url = $_['ios_url'] ?? null;

$pEol = function() {
  p("\n");
};

p($l->t('Hello %1$s,', [$recipient]));
$pEol();
$pEol();

print_unescaped($l->t('%1$s has requested your signature of "%2$s" on %3$s.', [
  $user->getDisplayName(),
  $file->getName(),
  $defaults->getName(),
]));
$pEol();
$pEol();

print_unescaped($l->t('Please open the following URL to sign the file:'));
$pEol();
print_unescaped($url);
$pEol();
$pEol();

if ($ios_url) {
  print_unescaped($l->t('If you have the Nextcloud Pro app installed on iOS, you can use the following link to sign the file:'));
  $pEol();
  print_unescaped($ios_url);
  $pEol();
  $pEol();
}

print_unescaped($l->t('Thanks'));
$pEol();
