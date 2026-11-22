# Installation

## Requirements

| Requirement | Needed for |
| --- | --- |
| **PHP 8.2+** | The whole library. |
| **`ext-fileinfo`** | Detecting the real MIME type of a file (used by validation and `File::getRealMimeType()`). Bundled with most PHP builds. |
| **`ext-ftp`** | Only the [FTP adapter](FTP-Adapter.md). |
| **`aws/aws-sdk-php`** | Only the [S3 adapter](S3-Adapter.md). |

The core package has **no runtime Composer dependencies**. The S3 SDK is a
*suggested* dependency you install only if you use the S3 adapter.

## Install

```bash
composer require initphp/upload
```

### For S3 uploads, also install the AWS SDK

```bash
composer require aws/aws-sdk-php
```

If you construct an [`S3Adapter`](S3-Adapter.md) without the SDK installed, the
constructor throws an [`UnsupportedException`](Error-Handling.md#unsupportedexception):

> AWS S3 SDK must be installed to use this adapter. Try running "composer require aws/aws-sdk-php".

### For FTP uploads, make sure `ext-ftp` is enabled

Most distributions ship it. Check with:

```bash
php -m | grep ftp
```

Constructing an [`FTPAdapter`](FTP-Adapter.md) without it throws an
`UnsupportedException`:

> FTP adapter cannot be used on your server.

## Verify the install

```php
<?php
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Upload\Upload;
use InitPHP\Upload\Adapters\LocalAdapter;

$upload = new Upload(new LocalAdapter([
    'dir' => sys_get_temp_dir(),
    'url' => 'https://example.test',
]));

echo get_class($upload); // InitPHP\Upload\Upload
```

If that runs without error, you are ready for the [Quick Start](Quick-Start.md).

## Versioning

`initphp/upload` follows [Semantic Versioning](https://semver.org/). Pin a
constraint that allows compatible updates:

```json
{
    "require": {
        "initphp/upload": "^3.0"
    }
}
```

## Namespaces

Everything lives under the `InitPHP\Upload` namespace:

| Class | Namespace |
| --- | --- |
| `Upload` | `InitPHP\Upload\Upload` |
| `File` | `InitPHP\Upload\File` |
| `LocalAdapter` | `InitPHP\Upload\Adapters\LocalAdapter` |
| `FTPAdapter` | `InitPHP\Upload\Adapters\FTPAdapter` |
| `S3Adapter` | `InitPHP\Upload\Adapters\S3Adapter` |
| `UploadAdapterInterface` | `InitPHP\Upload\Interfaces\UploadAdapterInterface` |
| `UploadException` | `InitPHP\Upload\Exceptions\UploadException` |
| `UnsupportedException` | `InitPHP\Upload\Exceptions\UnsupportedException` |
| `UploadInvalidArgumentException` | `InitPHP\Upload\Exceptions\UploadInvalidArgumentException` |
