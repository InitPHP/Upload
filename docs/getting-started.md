# Getting started

## Install

```bash
composer require initphp/upload
```

The package needs PHP 8.0+ and the `fileinfo` extension. The FTP adapter also
needs `ext-ftp`; the S3 adapter needs `aws/aws-sdk-php`
(`composer require aws/aws-sdk-php`).

## The three building blocks

```php
use InitPHP\Upload\Upload;                  // the object you call to()/setFile() on
use InitPHP\Upload\File;                     // one file to upload
use InitPHP\Upload\Adapters\LocalAdapter;    // a storage backend
```

An **adapter** knows *where* to store files and *what* to allow. You wrap it in
an **`Upload`** and hand it **`File`** objects.

```php
$adapter = new LocalAdapter(
    [   // credentials: where files go
        'dir' => __DIR__ . '/uploads/',
        'url' => 'https://example.com/uploads/',
    ],
    [   // options: what is allowed (optional)
        'allowed_extensions' => ['jpg', 'png'],
        'allowed_max_size'   => 1024 * 1024, // 1 MB
    ]
);

$upload = new Upload($adapter);
```

## Loading files

### From an HTML form (`$_FILES`)

`File::setPost()` reads a `$_FILES` key and always returns a `File[]`, so the
same loop handles a single-file field and a `name="photos[]"` multi-file field.

```php
foreach (File::setPost('photos') as $file) {
    $upload->setFile($file)->to();
}
```

Empty file inputs (`UPLOAD_ERR_NO_FILE`) are skipped. A field that errored for
another reason still returns a `File`; the error surfaces when you call `to()`.

### From an existing path

```php
use InitPHP\Upload\File;

$file = File::setPath('/var/data/report.pdf');
$upload->setFile($file)->to();
```

A path-loaded file is **copied** to its destination, leaving the original in
place. A real HTTP upload is **moved**.

## Storing a file

```php
$stored = $upload->setFile($file)->to();

if ($stored !== false) {
    echo $stored->getURL();
}
```

`to()` returns the stored `File` (with its public URL set), or `false` if the
underlying write returned false. Validation failures throw an
[`UploadException`](exceptions.md) rather than returning `false`.

### Storing under a sub-path

The optional `$target` is a destination prefix:

```php
$upload->setFile($file)->to('avatars/2026');
// Local: <dir>/avatars/2026/<name>
// FTP:   <remote>/avatars/2026/<name>
// S3:    key "avatars/2026/<name>" in the bucket
```

## Switching backends

Only the adapter changes; the rest of your code stays the same.

```php
use InitPHP\Upload\Adapters\FTPAdapter;

$upload = new Upload(new FTPAdapter([
    'host'     => 'ftp.example.com',
    'username' => 'user',
    'password' => 'secret',
    'url'      => 'https://cdn.example.com/',
]));

foreach (File::setPost('photos') as $file) {
    $upload->setFile($file)->to();
}
```

See the [local](local-adapter.md), [FTP](ftp-adapter.md) and [S3](s3-adapter.md)
guides for backend-specific details.
