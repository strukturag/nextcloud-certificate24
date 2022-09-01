# Nextcloud app OCS API

This document describes the OCS API of the esig Nextcloud app.

Unless otherwise noted, each request must be authenticated with a user of the
Nextcloud instance.

## Share document for signing

* Method: `POST`
* Endpoint: `/api/v1/share`
* Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `file_id`           | int     | The id of the file to be signed.                                 |
    | `recipient`         | string  | User id or email address to share the document with.             |
    | `recipient_type`    | string  | Type of recipient, can be `user` or `email`.                     |

* Response:
  - Status code:
    + `201 Created`
    + `400 Bad Request` When no / an invalid recipient was specified.
    + `401 Unauthorized` When the user is not logged in.
    + `403 Forbidden` When the user is not allowed to share documents for signing.
    + `404 Not Found` When the file was not found.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |


## Get list of shared documents

* Method: `GET`
* Endpoint: `/api/v1/share`
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
    | `user_id`           | string  | The id of the user that shared the file.                         |
    | `display_name`      | string  | The display name of the user that shared the file.               |


## Delete shared document

* Method: `DELETE`
* Endpoint: `/api/v1/share/<request_id>`
* Response:
  - Status code:
    + `200 OK`
    + `403 Forbidden` When the user is not allowed to access the request.
    + `404 Not Found` When no such request exists.


## Get details on shared document

* Method: `GET`
* Endpoint: `/api/v1/share/<request_id>`
* Response:
  - Status code:
    + `200 OK`
    + `403 Forbidden` When the user is not allowed to access the request.
    + `404 Not Found` When no such request exists.
    + `409 Conflict` Document was already signed.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |
    | `created`           | iso8601 | The timestamp when the file was shared.                          |
    | `user_id`           | string  | The id of the user that shared the file.                         |
    | `display_name`      | string  | The display name of the user that shared the file.               |
    | `download_url`      | string  | A temporary URL that can be used to download the unsigned file.  |
    | `signed`            | iso8601 | The timestamp when the file was signed (if already signed).      |
    | `result_url`        | string  | A temporary URL that can be used to download the signed file.    |

If the file was already signed, the field `download_url` will be omitted and
the fields `signed` and `result_url` will be populated.

This method can also be accessed by anonymous users if document was shared with
an email address.


## Sign document

* Method: `POST`
* Endpoint: `/api/v1/sign/<request_id>`
* Data:
  - metadata: JSON metadata to include in the signature.
  - signature: Optional image containing written signature.
* Response:
  - Status code:
    + `200 OK`
    + `403 Forbidden` When the user is not allowed to sign the document.
    + `409 Conflict` Document was already signed.
    + `404 Not Found` When no such request exists.
    + `504 Gateway Timeout` When the signing backend took too long.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |
    | `signed`            | iso8601 | The timestamp when the file was signed.                          |
    | `result_url`        | string  | A temporary URL that can be used to download the signed file.    |

This method can also be accessed by anonymous users if document was shared with
an email address.
