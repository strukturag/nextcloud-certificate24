<?php
use OC;
use OCP\Defaults;

$defaults = OC::$server->query(Defaults::class);
$file = $_['file'];

print_unescaped($l->t('Signatures finished for "%1$s" on %2$s', [
  $file->getName(),
  $defaults->getName(),
]));
