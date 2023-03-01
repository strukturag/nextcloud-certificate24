<?php

use OCP\Defaults;

$defaults = \OC::$server->query(Defaults::class);

$file = $_['file'];
$user = $_['user'];
$recipient = $_['recipient'];
$request_id = $_['request_id'];
$url = $_['url'];

$pEol = function () {
	p("\n");
};

p($l->t('Hello %1$s,', [$recipient]));
$pEol();
$pEol();

print_unescaped($l->t('all recipients have signed "%1$s" on %2$s.', [
	$file->getName(),
	$defaults->getName(),
]));
$pEol();
$pEol();

print_unescaped($l->t('You can find additional information and download the signed file from the following url:'));
$pEol();
print_unescaped($url);
$pEol();
$pEol();

print_unescaped($l->t('Thanks'));
$pEol();
