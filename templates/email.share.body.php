<?php
use OCP\Defaults;

$defaults = \OC::$server->query(Defaults::class);

$file = $_['file'];
$user = $_['user'];
$recipient = $_['recipient'];
$request_id = $_['request_id'];
$url = $_['url'];

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

print_unescaped($l->t('Thanks'));
$pEol();
