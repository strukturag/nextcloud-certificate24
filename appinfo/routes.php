<?php

declare(strict_types=1);

$requirements = [
	'apiVersion' => 'v(1)',
];

return [
	'routes' => [
		[
			'name' => 'Page#index',
			'url' => '/',
			'verb' => 'GET',
		],
		[
			'name' => 'Page#sign',
			'url' => '/sign/{id}',
			'verb' => 'GET',
		],
		[
			'name' => 'Download#downloadOriginal',
			'url' => '/download/{id}',
			'verb' => 'GET',
		],
		[
			'name' => 'Download#downloadSource',
			'url' => '/download/source/{id}',
			'verb' => 'GET',
		],
		[
			'name' => 'Download#downloadSigned',
			'url' => '/download/signed/{id}',
			'verb' => 'GET',
		],
		[
			'name' => 'Download#downloadSignatureImage',
			'url' => '/settings/signature',
			'verb' => 'GET',
		],
		[
			'name' => 'Download#uploadSignatureImage',
			'url' => '/settings/signature',
			'verb' => 'POST',
		],
		[
			'name' => 'Download#deleteSignatureImage',
			'url' => '/settings/signature',
			'verb' => 'DELETE',
		],
	],
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
			'name' => 'Api#getIncomingRequests',
			'url' => '/api/{apiVersion}/share/incoming',
			'verb' => 'GET',
			'requirements' => $requirements,
		],
		[
			'name' => 'Api#getIncomingRequest',
			'url' => '/api/{apiVersion}/share/incoming/{id}',
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
		[
			'name' => 'Api#signRequest',
			'url' => '/api/{apiVersion}/share/{id}/sign',
			'verb' => 'POST',
			'requirements' => $requirements,
		],
		[
			'name' => 'Api#getFileMetadata',
			'url' => '/api/{apiVersion}/metadata/{id}',
			'verb' => 'GET',
			'requirements' => $requirements,
		],
		[
			'name' => 'Api#search',
			'url' => '/api/{apiVersion}/search',
			'verb' => 'POST',
			'requirements' => $requirements,
		],
	],
];
