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
    | `recipients`        | array   | List of recipients to share the document with.                   |
    | `options`           | array   | JSON options for the request.                                    |
    | `metadata`          | array   | JSON metadata to include in the request.                         |

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


Each entry in the `recipients` array must contain the following fields:

  | field               | type    | description                                                      |
  |---------------------|---------|------------------------------------------------------------------|
  | `type`              | string  | Type of recipient, can be `user` or `email`.                     |
  | `value`             | string  | Userid (for type `user`) or email address (for type `email`).    |


The following fields are currently defined for the request `options` JSON:

  | field               | type    | description                                                      |
  |---------------------|---------|------------------------------------------------------------------|
  | `signed_save_mode`  | string  | How signed files should be processed (`new`, `replace`, `none`). |

If the `signed_save_mode` option is omitted, the configured system default will
be used. The value `new` will create a new signed file next to the original
file, `replace` will replace the original file with the signed file and `none`
will not download signed files automatically.


The following fields are currently defined for the request `metadata` JSON:

  | field               | type    | description                                                      |
  |---------------------|---------|------------------------------------------------------------------|
  | `version`           | string  | Metadata version, currently `1.0`.                               |
  | `signature_fields`  | array   | Array of objects definining the positions of signature fields.   |

Signature fields objects must contain the keys `id` (unique id of the field),
`page` (1-based page number), `x`, `y`, `width`, `height` with values
based on the page viewport where the top left of the page is at `0` / `0`.

If signatures are requested from multiple recipients, each signature field must
contain a `recipient_idx` field with the (0-based) index of the recipient that
should sign the field.


## Get metadata of file.

* Method: `GET`
* Endpoint: `/api/v1/metadata/<file_id>`

* Response:
  - Status code:
    + `401 Unauthorized` When the user is not logged in.
    + `403 Forbidden` When the user is not allowed to access the file.
    + `404 Not Found` When the file was not found.
  - Data:
     The metadata included in the previous request to share the file. See above
     for details. Could be an empty JSON object if no request was sent for the
     file before.


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
    | `recipients`        | array   | List of recipients the file was shared with.                     |
    | `metadata`          | array   | Optional request JSON metadata (see above).                      |
    | `signed`            | iso8601 | The timestamp when the file was signed completely.               |
    | `signed_url`        | string  | A temporary URL that can be used to download the signed file.    |

The field `signed` is only returned if `include_signed` was passed as `true` in
the request. Recipients that already signed the file will have an additional
field `signed` in their `recipients` entry.


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
    | `metadata`          | array   | Optional request JSON metadata (see above).                      |
    | `signed`            | iso8601 | The timestamp when the file was signed completely.               |
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
    | `recipients`        | array   | List of recipients the file was shared with.                     |
    | `metadata`          | array   | Optional request JSON metadata (see above).                      |
    | `signed`            | iso8601 | The timestamp when the file was signed completely.               |
    | `signed_url`        | string  | A temporary URL that can be used to download the signed file.    |

Recipients that already signed the file will have an additional field `signed`
in their `recipients` entry.


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
    | `metadata`          | array   | Optional JSON request metadata (see above).                      |
    | `signed`            | iso8601 | The timestamp when the file was signed completely.               |
    | `signed_url`        | string  | A temporary URL that can be used to download the signed file.    |

This method can also be accessed by anonymous users if file was shared with
an email address. In this case, an additional url query parameter `email` must
be given with the email address of the user that is fetching the information.


## Get details on file based on signature id

* Method: `GET`
* Endpoint: `/api/v1/signature/<signature_id>`
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
    | `metadata`          | array   | Optional JSON request metadata (see above).                      |
    | `signed`            | iso8601 | The timestamp when the file was signed completely.               |
    | `signed_url`        | string  | A temporary URL that can be used to download the signed file.    |


## Sign file

* Method: `POST`
* Endpoint: `/api/v1/share/<request_id>/sign`
* Content-Type: `multipart/formdata`
* Form fields:
  - `options`: JSON options for the signature.
  - `metadata`: JSON metadata to include in the signature (TO BE DEFINED).
  - `<field-id>`: Image to render on the given field.
* Response:
  - Status code:
    + `200 OK`
    + `400 Bad Request` When the request contents are invalid.
    + `403 Forbidden` When the user is not allowed to sign the file.
    + `404 Not Found` When no such request exists.
    + `409 Conflict` File was already signed.
    + `412 Precondition Failed` Not all signature fields are filled out.
    + `413 Request Entity Too Large` Image is too large.
    + `504 Gateway Timeout` When the signing backend took too long.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `request_id`        | string  | The id of the signing request.                                   |
    | `signed`            | iso8601 | The timestamp when the file was signed.                          |

This method can also be accessed by anonymous users if file was shared with
an email address.

The following fields are currently defined for the `options` JSON:

  | field                  | type    | description                                                      |
  |------------------------|---------|------------------------------------------------------------------|
  | `email`                | string  | Email address if signing as anonymous user.                      |
  | `embed_user_signature` | bool    | Embed the personal signature image in all fields.                |


For every signature field defined in the request `metadata`, an image file can
be provided in the request by setting the form field name to the `id` of the
signature field from the request `metadata`. This will override images set by
the `embed_user_signature` option.

Make sure to include a `Content-Type` header with the correct mimetype of the
image.

Example:

    POST /url HTTP/1.1
    HOST: host.example.com
    Cookie: some_cookies...
    Connection: Keep-Alive
    Content-Type: multipart/form-data; boundary=abcdefg
    Content-Length: 12345

    --abcdefg
    Content-Disposition: form-data; name="signature-01"; filename="sig01.jpg"
    Content-Type: image/jpeg

    ...jpeg-image-data...
    --abcdefg
    Content-Disposition: form-data; name="signature-02"; filename="sig02.png"
    Content-Type: image/png

    ...png-image-data...
    --abcdefg--


If the same image should be used for multiple signature fields, the name of
another field can be given instead of uploading the same file multiple times.

Example:

    POST /url HTTP/1.1
    HOST: host.example.com
    Cookie: some_cookies...
    Connection: Keep-Alive
    Content-Type: multipart/form-data; boundary=abcdefg
    Content-Length: 12345

    --abcdefg
    Content-Disposition: form-data; name="signature-01"; filename="sig01.jpg"
    Content-Type: image/jpeg

    ...jpeg-image-data...
    --abcdefg
    Content-Disposition: form-data; name="signature-02"

    signature-01
    --abcdefg--


## Verify signatures of file

* Method: `GET`
* Endpoint: `/api/v1/verify/<file_id>`
* Query parameters:
  - `reverify`: Set to true to force (re-)verification of file.
* Response:
  - Status code:
    + `200 OK`
    + `403 Forbidden` When the user is not allowed to access the file.
    + `404 Not Found` File does not exist or does not contain signatures.
    + `412 Precondition Failed` The file was not verified yet.
    + `502 Bad Gateway` An error occurred while verifying the signatures.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `version`           | string  | Static text `1.0` for the current definition.                    |
    | `status`            | string  | The status of the files.                                         |
    | `verified`          | iso8601 | Timestamp when the file was verified.                            |
    | `validation`        | string  | The validation status of the files signatures (if signed).       |
    | `signatures`        | array   | The list of signatures (if signed).                              |

Possible values for the field `status` are `signed` or `not_signed`.

The field `validation` can contain one of the following:
- `valid`: All signatures could be verified successfully.
- `format_error`: One of the signatures contains an invalid format.
- `error_validating`: An error occurred while validating the signatures.
- `certificate_not_valid`: One of the certificates was not valid at the time of signing.
- `certificate_expired`: One of the certificates was expired at the time of signing.
- `no_signature_date`: One of the signatures doesn't contain a date of signing.
- `signature_date_invalid`: One of the signing dates is invalid.
- `unknown_ca`: The file was signed by an unknown CA.
- `invalid_signature`: The signature is invalid.

The entries in `signatures` contain these fields:

  | field                  | type    | description                                                      |
  |------------------------|---------|------------------------------------------------------------------|
  | `validation`           | string  | The validation status of this signature (see above).             |
  | `whole_file`           | bool    | Does the signature cover the whole file?                         |
  | `signed`               | iso8601 | Timestamp of this signature (if available).                      |
  | `signed_details`       | object  | Details of the signature timestamp (if available).               |
  | `certificates`         | array   | List of PEM-encoded X.509 certificates for this signature.       |

The following fields are defined for `signed_details` and are available if the
signature timestamp was signed by a timestamp authority:

  | field                  | type    | description                                                      |
  |------------------------|---------|------------------------------------------------------------------|
  | `validation`           | string  | The validation status of this timestamp signature (see above).   |
  | `qualified`            | bool    | Is the timestamp signature qualified?                            |
  | `certificates`         | array   | List of PEM-encoded X.509 certificates for this timestamp.       |


# Backend APIs

In some cases, it might be necessary to request information directly from the
signature backend server. One example would be querying details of anonymous
signatures based on the signature id of the backend service (e.g. from the
signing request email to anonymous users).


## Get details on anonymous signature

* Method: `GET`
* Endpoint: `/api/v1/signatures/<signature_id>`
* Headers:
   - `X-Vinegar-API`: `true`
* Response:
  - Status code:
    + `200 OK`
    + `404 Not Found` When no such signature exists.
    + `409 Conflict` When the signature was already performed.
  - Data:
    | field               | type    | description                                                      |
    |---------------------|---------|------------------------------------------------------------------|
    | `status`            | string  | `success`                                                        |
    | `url`               | string  | The URL of the Nextcloud instance for this signature request.    |
    | `signature_id`      | string  | The id of the signature request for this recipient.              |
    | `recipient`         | array   | Information on the recipient of the signature request.           |

The `signature_id` can be used to query information about the request (use the
"Get details on file based on signature id" API from above).
