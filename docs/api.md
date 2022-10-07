# Nextcloud app API

This document describes the API of the esig Nextcloud app.

Unless otherwise noted, each request must be authenticated with a user of the
Nextcloud instance.

# OCS APIs

## Share file for signing

* Method: `POST`
* Endpoint: `/api/v1/share`
* Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `file_id`           | int     | The id of the file to be signed.                                 |
    | `recipient`         | string  | User id or email address to share the document with.             |
    | `recipient_type`    | string  | Type of recipient, can be `user` or `email`.                     |
    | `metadata`          | array   | JSON metadata to include in the request (TO BE DEFINED).         |

* Response:
  - Status code:
    + `201 Created`
    + `400 Bad Request` When no / an invalid recipient was specified.
    + `401 Unauthorized` When the user is not logged in.
    + `403 Forbidden` When the user is not allowed to share files for signing.
    + `404 Not Found` When the file was not found.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |


## Get list of files shared by current user

* Method: `GET`
* Endpoint: `/api/v1/share`
* Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `include_signed`    | bool    | Should signed files be included?                                 |

* Response:
  - Status code:
    + `200 OK`
    + `401 Unauthorized` When the user is not logged in.
  - Data:
    List of document objects with the following properties.

    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |
    | `created`           | iso8601 | The timestamp when the file was shared.                          |
    | `file_id`           | int     | The id of the file to be signed.                                 |
    | `filename`          | string  | Filename that was shared.                                        |
    | `mimetype`          | string  | Mimetype of the shared file.                                     |
    | `download_url`      | string  | A temporary URL that can be used to download the original file.  |
    | `recipient`         | string  | User id or email address to share the document with.             |
    | `recipient_type`    | string  | Type of recipient, can be `user` or `email`.                     |
    | `metadata`          | array   | Optional JSON metadata.                                          |
    | `signed`            | iso8601 | The timestamp when the file was signed or `null`.                |
    | `signed_url`        | string  | A temporary URL that can be used to download the signed file.    |

The field `signed` is only returned if `include_signed` was passed as `true` in
the request.


## Get list of files requested to by signed by current user

* Method: `GET`
* Endpoint: `/api/v1/share/incoming`
* Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `include_signed`    | bool    | Should signed files be included?                                 |

* Response:
  - Status code:
    + `200 OK`
    + `401 Unauthorized` When the user is not logged in.
  - Data:
    List of share request objects with the following properties.

    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |
    | `created`           | iso8601 | The timestamp when the file was shared.                          |
    | `user_id`           | string  | User id of user requesting the signature.                        |
    | `display_name`      | string  | The display name of the user that shared the file.               |
    | `filename`          | string  | Filename that was shared.                                        |
    | `mimetype`          | string  | Mimetype of the shared file.                                     |
    | `download_url`      | string  | A temporary URL that can be used to download the original file.  |
    | `metadata`          | array   | Optional JSON metadata.                                          |
    | `signed`            | iso8601 | The timestamp when the file was signed or `null`.                |
    | `signed_url`        | string  | A temporary URL that can be used to download the signed file.    |

The field `signed` is only returned if `include_signed` was passed as `true` in
the request.


## Delete file signing request

* Method: `DELETE`
* Endpoint: `/api/v1/share/<request_id>`
* Response:
  - Status code:
    + `200 OK`
    + `403 Forbidden` When the user is not allowed to access the request.
    + `404 Not Found` When no such request exists.


## Get details on file shared for signing by current user

* Method: `GET`
* Endpoint: `/api/v1/share/<request_id>`
* Response:
  - Status code:
    + `200 OK`
    + `403 Forbidden` When the user is not allowed to access the request.
    + `404 Not Found` When no such request exists.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |
    | `created`           | iso8601 | The timestamp when the file was shared.                          |
    | `file_id`           | int     | The id of the file to be signed.                                 |
    | `filename`          | string  | Filename that was shared.                                        |
    | `mimetype`          | string  | Mimetype of the shared file.                                     |
    | `download_url`      | string  | A temporary URL that can be used to download the original file.  |
    | `recipient`         | string  | User id or email address to share the document with.             |
    | `recipient_type`    | string  | Type of recipient, can be `user` or `email`.                     |
    | `metadata`          | array   | Optional JSON metadata.                                          |
    | `signed`            | iso8601 | The timestamp when the file was signed (if already signed).      |
    | `signed_url`        | string  | A temporary URL that can be used to download the signed file.    |


## Get details on file shared for signing

* Method: `GET`
* Endpoint: `/api/v1/share/incoming/<request_id>`
* Response:
  - Status code:
    + `200 OK`
    + `401 Unauthorized` When the user is not allowed to access the request.
    + `404 Not Found` When no such request exists.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |
    | `created`           | iso8601 | The timestamp when the file was shared.                          |
    | `user_id`           | string  | The id of the user that shared the file.                         |
    | `display_name`      | string  | The display name of the user that shared the file.               |
    | `filename`          | string  | Filename that was shared.                                        |
    | `mimetype`          | string  | Mimetype of the shared file.                                     |
    | `download_url`      | string  | A temporary URL that can be used to download the original file.  |
    | `metadata`          | array   | Optional JSON metadata.                                          |
    | `signed`            | iso8601 | The timestamp when the file was signed (if already signed).      |
    | `signed_url`        | string  | A temporary URL that can be used to download the signed file.    |

This method can also be accessed by anonymous users if file was shared with
an email address.


## Sign file

* Method: `POST`
* Endpoint: `/api/v1/sign/<request_id>`
* Data:
  - metadata: JSON metadata to include in the signature (TO BE DEFINED).
  - signature: Optional image containing written signature (TO BE DEFINED).
* Response:
  - Status code:
    + `200 OK`
    + `403 Forbidden` When the user is not allowed to sign the file.
    + `409 Conflict` File was already signed.
    + `404 Not Found` When no such request exists.
    + `504 Gateway Timeout` When the signing backend took too long.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |
    | `signed`            | iso8601 | The timestamp when the file was signed.                          |
    | `signed_url`        | string  | A temporary URL that can be used to download the signed file.    |

This method can also be accessed by anonymous users if file was shared with
an email address.
