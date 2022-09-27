<?php

declare(strict_types=1);

$requirements = [
	'apiVersion' => 'v(1)',
];

return [
	'ocs' => [
		[
      'name' => 'Api#shareFile',
      'url' => '/api/{apiVersion}/share',
      'verb' => 'POST',
      'requirements' => $requirements,
    ],
  ],
];
