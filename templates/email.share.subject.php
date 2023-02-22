<?php
use OC;
use OCP\Defaults;

$defaults = OC::$server->query(Defaults::class);

print_unescaped($l->t('Signing request on %1$s', [$defaults->getName()]));
