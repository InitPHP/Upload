# InitPHP Upload

Validate and store uploaded files on local disk, FTP/FTPS or Amazon S3 through
a single, adapter-based API.

[![Designed & Maintained with Tan](https://muhammetsafak.com/badges/designed-maintained-with-tan.svg)](https://muhammetsafak.com/tan/)
[![CI](https://github.com/InitPHP/Upload/actions/workflows/ci.yml/badge.svg)](https://github.com/InitPHP/Upload/actions/workflows/ci.yml)
[![Latest Stable Version](http://poser.pugx.org/initphp/upload/v)](https://packagist.org/packages/initphp/upload) [![Total Downloads](http://poser.pugx.org/initphp/upload/downloads)](https://packagist.org/packages/initphp/upload) [![License](http://poser.pugx.org/initphp/upload/license)](https://packagist.org/packages/initphp/upload) [![PHP Version Require](http://poser.pugx.org/initphp/upload/require/php)](https://packagist.org/packages/initphp/upload)

## Requirements

- PHP 8.2 or higher
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
    'dir' => '/var/app/storage/uploads/',        // outside the web root
    'url' => 'https://example.com/uploads/',     // served read-only, scripts never executed
], [
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'], // real type, via finfo
    'allowed_max_size'   => 2 * 1024 * 1024, // 2 MB, in bytes
]);

$upload = new Upload($adapter);

foreach (File::setPost('photos') as $file) {
    // Never store under the client's file name: generate one (the extension is kept).
    $file->rename(bin2hex(random_bytes(16)));

    $stored = $upload->setFile($file)->to();

    if ($stored !== false) {
        echo 'Uploaded to: ' . htmlspecialchars((string) $stored->getURL(), ENT_QUOTES, 'UTF-8');
    }
}
```

`File::setPost('photos')` reads `$_FILES['photos']` and returns a normalized
`File[]`, whether the field uploaded one file or many. `to()` validates the
file, stores it, and returns the stored `File` (with its public URL set) or
`false` if the underlying write failed. Any validation or transfer problem is
thrown as an [`UploadException`](docs/Error-Handling.md).

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
| [`LocalAdapter`](docs/Local-Adapter.md) | `dir`, `url` | Creates missing destination directories. |
| [`FTPAdapter`](docs/FTP-Adapter.md) | `host`, `port`, `username`, `password`, `timeout`, `url`, `passive`, `ssl` | Binary mode, passive by default, optional FTPS. |
| [`S3Adapter`](docs/S3-Adapter.md) | `key`, `secret_key`, `region`, `bucket`, `ACL`, `version` | Requires `aws/aws-sdk-php`. |

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

> **Security:** with no options set, **every** extension and type is accepted, and
> the file is stored under the name the client sent. Always set
> `allowed_extensions` and `allowed_mime_types` to an allow-list, `rename()` each
> file, and keep `dir` outside the web root. See
> [Security Best Practices](docs/Security-Best-Practices.md).

## Renaming

```php
$file->rename('profile');          // keeps the original extension
$upload->setFile($file)->to();     // stored as profile.jpg
```

## Documentation

Full guides with examples live in [`docs/`](docs/Home.md):

- [Installation](docs/Installation.md) · [Quick Start](docs/Quick-Start.md)
- [Core Concepts](docs/Core-Concepts.md)
- [The `File` object](docs/The-File-Object.md)
- [Validation](docs/Validation.md)
- [Local adapter](docs/Local-Adapter.md) ·
  [FTP adapter](docs/FTP-Adapter.md) ·
  [S3 adapter](docs/S3-Adapter.md)
- [Error Handling](docs/Error-Handling.md)
- [Security Best Practices](docs/Security-Best-Practices.md)
- [API Reference](docs/API-Reference.md) · [Recipes](docs/Recipes.md) · [Testing](docs/Testing.md)
- [FAQ](docs/FAQ.md) · [Troubleshooting](docs/Troubleshooting.md)

## Contributing

Bug reports and pull requests are welcome. CI runs PHP-CS-Fixer, PHPStan (max
level) and PHPUnit across PHP 8.2–8.6; run the same bundle locally with:

```bash
composer ci
```

## Credits

- [Muhammet ŞAFAK](https://muhammetsafak.com) <<info@muhammetsafak.com>>

## License

Copyright &copy; 2023 InitPHP — released under the [MIT License](./LICENSE).
