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
		[
      'name' => 'Api#getRequests',
      'url' => '/api/{apiVersion}/share',
      'verb' => 'GET',
      'requirements' => $requirements,
    ],
		[
      'name' => 'Api#getRequest',
      'url' => '/api/{apiVersion}/share/{id}',
      'verb' => 'GET',
      'requirements' => $requirements,
    ],
		[
      'name' => 'Api#deleteRequest',
      'url' => '/api/{apiVersion}/share/{id}',
      'verb' => 'DELETE',
      'requirements' => $requirements,
    ],
  ],
];
