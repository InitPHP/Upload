# InitPHP Upload — Documentation

InitPHP Upload validates and stores uploaded files behind a single adapter
interface. Point it at the local filesystem, an FTP/FTPS server, or an Amazon
S3 bucket without changing the calling code.

## Contents

| Guide | What it covers |
| ----- | -------------- |
| [Getting started](getting-started.md) | Install, the two ways to load a file, your first upload. |
| [The `File` object](the-file-object.md) | Every accessor on `File`, plus `setPost()` and `setPath()`. |
| [Validation](validation.md) | Extension, MIME type and size restrictions and how they are checked. |
| [Local adapter](local-adapter.md) | Storing files on disk, move vs. copy, directory creation. |
| [FTP adapter](ftp-adapter.md) | FTP/FTPS uploads, passive mode, remote directories. |
| [S3 adapter](s3-adapter.md) | Amazon S3 uploads, buckets, keys and ACLs. |
| [Exceptions](exceptions.md) | What is thrown, when, and how to catch it. |

## At a glance

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\LocalAdapter;

$upload = new Upload(new LocalAdapter([
    'dir' => __DIR__ . '/uploads/',
    'url' => 'https://example.com/uploads/',
]));

foreach (File::setPost('photos') as $file) {
    $stored = $upload->setFile($file)->to('2026/06');
    if ($stored !== false) {
        echo $stored->getURL(); // https://example.com/uploads/2026/06/<name>
    }
}
```

## Core concepts

- **`File`** — a value object describing one file to upload. Build a list from
  `$_FILES` with `File::setPost()`, or wrap an existing path with
  `File::setPath()`.
- **Adapter** — a storage backend (`LocalAdapter`, `FTPAdapter`, `S3Adapter`).
  It takes `credentials` (where to store) and `options` (what to allow).
- **`Upload`** — a thin decorator over an adapter. It is the object you call
  `setFile()` and `to()` on.
- **`$target`** — the optional destination path/key prefix passed to `to()`,
  with the same meaning in every adapter.

## Requirements

- PHP 8.0 or newer
- `ext-fileinfo` (real MIME detection)
- `ext-ftp` for the FTP adapter; `aws/aws-sdk-php` for the S3 adapter
