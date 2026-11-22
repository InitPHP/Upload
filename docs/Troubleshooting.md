# Troubleshooting

Concrete fixes for the problems people hit most often.

## "The file to be uploaded is undefined."

You called `to()` without first calling `setFile()`.

```php
$upload->setFile($file)->to(); // setFile() is required before to()
```

If you are looping over `File::setPost()` and the loop never runs, the field
was empty or absent — `setPost()` returned `[]`. Check the form's `name`
attribute and that it has `enctype="multipart/form-data"`.

## "This file extension is not allowed."

The file's extension is not in `allowed_extensions`. Remember:

- Matching is case-insensitive, so you only need `['jpg']`, not `['jpg','JPG']`.
- The extension comes from the **original name**. A file with no extension
  (`README`) has an empty extension and fails any non-empty allow-list.

## "This file type is not allowed."

The **real** MIME type (from `finfo`) is not in `allowed_mime_types`. This is
working as intended when a file's real content differs from its name. To see
what `finfo` detected:

```php
echo $file->getRealMimeType();
```

If a legitimate file is rejected, add its real type to the list (e.g. some
browsers send `image/jpg` while `finfo` reports `image/jpeg`).

## "Exceeds the maximum uploadable file size."

The file is larger than `allowed_max_size` (which is in **bytes**). Raise the
limit, or compress the file. Also confirm your `php.ini` limits are high enough:

```ini
upload_max_filesize = 16M
post_max_size       = 20M
```

A file over the PHP limits arrives with an upload error code, surfacing as
*"The file upload failed with error code N."* instead.

## "The file upload failed with error code N."

The browser/PHP reported an [`UPLOAD_ERR_*`](https://www.php.net/manual/en/features.file-upload.errors.php)
problem before validation:

| N | Constant | Meaning |
| --- | --- | --- |
| 1 | `UPLOAD_ERR_INI_SIZE` | Bigger than `upload_max_filesize`. |
| 2 | `UPLOAD_ERR_FORM_SIZE` | Bigger than the form's `MAX_FILE_SIZE`. |
| 3 | `UPLOAD_ERR_PARTIAL` | Only part of the file was received. |
| 6 | `UPLOAD_ERR_NO_TMP_DIR` | No temp directory configured. |
| 7 | `UPLOAD_ERR_CANT_WRITE` | Could not write to disk. |

Fix the underlying server setting; codes 6 and 7 are server-side
misconfiguration.

## `to()` returns `false`

Not an exception — the write itself failed. Common causes:

- **Local:** `dir` is not writable by the PHP process. Check ownership/permissions.
  (A destination directory that cannot be created throws an `UploadException`
  instead.)
- **FTP:** the `ftp_fput` transfer returned false (server rejected the path or
  ran out of space).
- **S3:** the SDK response carried no `ObjectURL`.

## Local files are not where I expect

- Confirm `dir` is what you think — log `realpath($dir)`.
- Remember `$target` is appended under `dir`. `to('a/b')` ⇒ `<dir>/a/b/<name>`.
- The public URL is `url` + `/` + the same prefixed name; if the URL is wrong,
  fix `url`, not the storage path.

## My uploaded file replaced an old one

There is no automatic collision protection. Generate unique names with
`rename()` — see [Recipes](Recipes.md#generate-a-safe-unique-file-name).

## FTP uploads fail or hang

- **Passive mode.** Behind NAT/firewalls, active mode often stalls. Passive is
  the default; make sure you did not set `'passive' => false`.
- **Credentials.** *"FTP username or password incorrect!"* means login failed —
  verify `username`/`password`.
- **Connection.** *"FTP connection failed."* means the host/port was
  unreachable — check `host`, `port`, and network/firewall rules; raise
  `timeout` for slow links.
- **`ext-ftp` missing.** An `UnsupportedException` at construction means the FTP
  extension is not installed (`php -m | grep ftp`).
- **Corrupted binaries?** This package always transfers in `FTP_BINARY`, so
  corruption from this library should not happen; if it does, suspect the
  server or an intermediate proxy.

## "FTP adapter cannot be used on your server."

`ext-ftp` is not loaded. Install/enable it, or use a different adapter.

## "AWS S3 SDK must be installed to use this adapter."

The `aws/aws-sdk-php` package is missing:

```bash
composer require aws/aws-sdk-php
```

## S3 uploads throw an `UploadException`

The underlying AWS exception is wrapped — read the cause:

```php
catch (\InitPHP\Upload\Exceptions\UploadException $e) {
    $aws = $e->getPrevious(); // the original Aws\S3\Exception\S3Exception
    error_log((string) $aws);
}
```

Common causes: wrong `region`, a non-existent `bucket`, invalid
`key`/`secret_key`, or an IAM policy that forbids `s3:PutObject`.

## S3-compatible endpoints

This adapter configures the SDK with `version`, `region` and credentials only.
Providers that need a custom `endpoint` / path-style addressing (MinIO,
Cloudflare R2, DigitalOcean Spaces) are best driven by constructing
`Aws\S3\S3Client` yourself with the right options, then uploading through the
SDK. For most AWS-hosted buckets the adapter works as-is.

## Still stuck?

Open an [issue](https://github.com/InitPHP/Upload/issues/new) with your PHP
version, the adapter, the exact message, and a minimal reproducer. Doc and bug
fixes are merged eagerly.
