# InitPHP Upload

Validate and store uploaded files on local disk, FTP/FTPS or Amazon S3 through
a single, adapter-based API.

[![CI](https://github.com/InitPHP/Upload/actions/workflows/ci.yml/badge.svg)](https://github.com/InitPHP/Upload/actions/workflows/ci.yml)
[![Latest Stable Version](http://poser.pugx.org/initphp/upload/v)](https://packagist.org/packages/initphp/upload) [![Total Downloads](http://poser.pugx.org/initphp/upload/downloads)](https://packagist.org/packages/initphp/upload) [![License](http://poser.pugx.org/initphp/upload/license)](https://packagist.org/packages/initphp/upload) [![PHP Version Require](http://poser.pugx.org/initphp/upload/require/php)](https://packagist.org/packages/initphp/upload)

## Requirements

- PHP 8.0 or higher
- `ext-fileinfo` (used to detect the real MIME type of a file)
- `ext-ftp` — only for the FTP adapter
- [`aws/aws-sdk-php`](https://packagist.org/packages/aws/aws-sdk-php) — only for the S3 adapter

## Installation

```bash
composer require initphp/upload
```

For S3 uploads, also install the AWS SDK:

```bash
composer require aws/aws-sdk-php
```

## Quick start

```php
require 'vendor/autoload.php';

use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\LocalAdapter;

$adapter = new LocalAdapter([
    'dir' => __DIR__ . '/uploads/',
    'url' => 'https://example.com/uploads/',
], [
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'allowed_max_size'   => 2 * 1024 * 1024, // 2 MB, in bytes
]);

$upload = new Upload($adapter);

foreach (File::setPost('photos') as $file) {
    $stored = $upload->setFile($file)->to();

    if ($stored !== false) {
        echo 'Uploaded to: ' . $stored->getURL();
    }
}
```

`File::setPost('photos')` reads `$_FILES['photos']` and returns a normalized
`File[]`, whether the field uploaded one file or many. `to()` validates the
file, stores it, and returns the stored `File` (with its public URL set) or
`false` if the underlying write failed. Any validation or transfer problem is
thrown as an [`UploadException`](docs/exceptions.md).

## Loading files

| Call | Returns | Purpose |
| ---- | ------- | ------- |
| `File::setPost(string $key)` | `File[]` | Normalize `$_FILES[$key]` (single or multiple) into `File` objects. |
| `File::setPath(string $path)` | `File` | Wrap an existing file already on disk. |

A file loaded with `setPath()` is **copied** to its destination (the source is
left in place); a real HTTP upload is **moved** with `move_uploaded_file()`.

## Adapters

All three adapters share the same API — only the credentials differ. The
optional second constructor argument is the validation options, identical
across adapters.

| Adapter | Credentials | Notes |
| ------- | ----------- | ----- |
| [`LocalAdapter`](docs/local-adapter.md) | `dir`, `url` | Creates missing destination directories. |
| [`FTPAdapter`](docs/ftp-adapter.md) | `host`, `port`, `username`, `password`, `timeout`, `url`, `passive`, `ssl` | Binary mode, passive by default, optional FTPS. |
| [`S3Adapter`](docs/s3-adapter.md) | `key`, `secret_key`, `region`, `bucket`, `ACL`, `version` | Requires `aws/aws-sdk-php`. |

### The `to($target)` argument

`$target` means the same thing in every adapter: a **destination path/key
prefix**. `to('avatars/2026')` stores the file under that sub-path:

- Local → `<dir>/avatars/2026/<name>`
- FTP → `<remote>/avatars/2026/<name>` (directories created as needed)
- S3 → object key `avatars/2026/<name>` in the configured bucket

Call `to()` with no argument to store the file at the destination root.

## Validation options

Pass these as the second constructor argument of any adapter (an empty array or
`0` means "no restriction"):

| Option | Type | Meaning |
| ------ | ---- | ------- |
| `allowed_extensions` | `string[]` | Allowed file extensions, matched case-insensitively against the original file name. |
| `allowed_mime_types` | `string[]` | Allowed MIME types, matched against the **real** type detected with `finfo` (not the client-supplied one). |
| `allowed_max_size` | `int` | Maximum size in **bytes**. |

A file that violates any restriction — or whose upload errored — makes `to()`
throw an `UploadException`.

## Renaming

```php
$file->rename('profile');          // keeps the original extension
$upload->setFile($file)->to();     // stored as profile.jpg
```

## Documentation

Full guides with examples live in [`docs/`](docs/README.md):

- [Getting started](docs/getting-started.md)
- [The `File` object](docs/the-file-object.md)
- [Validation](docs/validation.md)
- [Local adapter](docs/local-adapter.md)
- [FTP adapter](docs/ftp-adapter.md)
- [S3 adapter](docs/s3-adapter.md)
- [Exceptions](docs/exceptions.md)

## Contributing

Bug reports and pull requests are welcome. CI runs PHP-CS-Fixer, PHPStan (max
level) and PHPUnit across PHP 8.0–8.4; run the same bundle locally with:

```bash
composer ci
```

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2023 InitPHP — released under the [MIT License](./LICENSE).
