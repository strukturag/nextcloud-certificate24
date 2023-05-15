<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022, struktur AG.
 *
 * @author Joachim Bauch <bauch@struktur.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

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
			'name' => 'Api#getSignatureRequest',
			'url' => '/api/{apiVersion}/signature/{id}',
			'verb' => 'GET',
			'requirements' => $requirements,
		],
		[
			'name' => 'Api#getFileMetadata',
			'url' => '/api/{apiVersion}/metadata/{id}',
			'verb' => 'GET',
			'requirements' => $requirements,
		],
		[
			'name' => 'Verify#getFileSignatures',
			'url' => '/api/{apiVersion}/verify/{id}',
			'verb' => 'GET',
			'requirements' => $requirements,
		],
		[
			'name' => 'Verify#clearCache',
			'url' => '/api/{apiVersion}/verify/cache',
			'verb' => 'DELETE',
			'requirements' => $requirements,
		],
		[
			'name' => 'Api#notifySigned',
			'url' => '/api/{apiVersion}/share/{id}/signed/{signature}',
			'verb' => 'POST',
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
