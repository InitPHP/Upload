# Recipes

Practical, copy-pasteable patterns. All examples assume:

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\LocalAdapter;
use InitPHP\Upload\Exceptions\UploadException;
```

## A complete upload endpoint

```html
<form method="post" action="/upload.php" enctype="multipart/form-data">
    <input type="file" name="photo" accept="image/*">
    <button>Upload</button>
</form>
```

```php
// upload.php
$upload = new Upload(new LocalAdapter(
    ['dir' => __DIR__ . '/uploads/', 'url' => 'https://example.com/uploads/'],
    [
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
        'allowed_max_size'   => 4 * 1024 * 1024,
    ]
));

$files = File::setPost('photo');

if ($files === []) {
    http_response_code(400);
    exit('No file uploaded.');
}

try {
    $stored = $upload->setFile($files[0])->to();
    if ($stored === false) {
        http_response_code(500);
        exit('Could not store the file.');
    }
    echo 'Stored at ' . $stored->getURL();
} catch (UploadException $e) {
    http_response_code(422);
    echo $e->getMessage();
}
```

## Multi-file gallery, returning JSON

```php
header('Content-Type: application/json');

$results = [];
foreach (File::setPost('gallery') as $file) {
    try {
        $stored = $upload->setFile($file)->to('gallery/' . date('Y/m'));
        $results[] = $stored === false
            ? ['name' => $file->getName(), 'ok' => false, 'error' => 'write failed']
            : ['name' => $file->getName(), 'ok' => true, 'url' => $stored->getURL()];
    } catch (UploadException $e) {
        $results[] = ['name' => $file->getName(), 'ok' => false, 'error' => $e->getMessage()];
    }
}

echo json_encode($results);
```

## Generate a safe, unique file name

The library stores the original name by default. To avoid collisions and
sanitize user-controlled names, rename each file before storing. `rename()`
keeps the original extension automatically.

```php
function uniqueName(): string
{
    return bin2hex(random_bytes(16)); // 32 hex chars, no extension
}

foreach (File::setPost('photos') as $file) {
    $file->rename(uniqueName());           // -> <32hex>.jpg
    $stored = $upload->setFile($file)->to('uploads');
}
```

A slug-style name derived from the original:

```php
$base = pathinfo($file->getName(), PATHINFO_FILENAME);
$slug = preg_replace('/[^a-z0-9]+/i', '-', $base);
$file->rename(strtolower(trim($slug, '-')) . '-' . substr(bin2hex(random_bytes(4)), 0, 8));
```

## Avatars: one file per user, organized by id

```php
$file = File::setPost('avatar')[0] ?? null;

if ($file !== null) {
    $file->rename('avatar'); // avatar.<ext>
    $stored = $upload->setFile($file)->to('users/' . $userId);
    // stored at <dir>/users/<id>/avatar.<ext>
    $user->avatar_url = $stored !== false ? $stored->getURL() : null;
}
```

## Images only — strong validation

Pair the extension and real-MIME checks so a renamed script cannot slip
through. See [Validation](Validation.md) and
[Security Best Practices](Security-Best-Practices.md).

```php
$imageOptions = [
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
    'allowed_max_size'   => 5 * 1024 * 1024,
];

$upload = new Upload(new LocalAdapter($credentials, $imageOptions));
```

## Stricter rules for one request, without a new adapter

`with*` methods return a fresh `Upload` and never mutate the original:

```php
$base = new Upload(new LocalAdapter($credentials, $defaultOptions));

// Thumbnails must be small; the shared $base is untouched.
$thumbs = $base->withOptions(['allowed_max_size' => 256 * 1024]);

$thumbs->setFile($file)->to('thumbnails');
```

## Choose the backend by environment

Because every adapter shares one interface, only construction differs.

```php
use InitPHP\Upload\Adapters\FTPAdapter;
use InitPHP\Upload\Adapters\S3Adapter;

$options = ['allowed_extensions' => ['jpg', 'png'], 'allowed_max_size' => 2_000_000];

$adapter = match (getenv('UPLOAD_DRIVER') ?: 'local') {
    's3'  => new S3Adapter([
        'key'        => getenv('AWS_ACCESS_KEY_ID'),
        'secret_key' => getenv('AWS_SECRET_ACCESS_KEY'),
        'region'     => getenv('AWS_REGION'),
        'bucket'     => getenv('AWS_BUCKET'),
    ], $options),
    'ftp' => new FTPAdapter([
        'host'     => getenv('FTP_HOST'),
        'username' => getenv('FTP_USER'),
        'password' => getenv('FTP_PASSWORD'),
        'url'      => getenv('FTP_PUBLIC_URL'),
    ], $options),
    default => new LocalAdapter([
        'dir' => __DIR__ . '/uploads/',
        'url' => getenv('APP_URL') . '/uploads/',
    ], $options),
};

$upload = new Upload($adapter);
// The rest of your code is identical regardless of $adapter.
```

## Re-upload an asset you already have on disk

`File::setPath()` **copies** (never moves) for the local adapter, so your
source survives:

```php
$file = File::setPath('/var/app/seed/default-avatar.png');
$upload->setFile($file)->to('defaults');
// /var/app/seed/default-avatar.png is untouched.
```

## Reject empty submissions cleanly

`File::setPost()` returns `[]` when the field is absent and skips empty file
inputs, so a simple guard covers both:

```php
$files = File::setPost('attachments');
if ($files === []) {
    // nothing was uploaded
}
```

## Validate a file before deciding what to do with it

```php
$file = File::setPost('doc')[0] ?? null;

if ($file !== null && $file->isValid()) {
    if ($file->getRealMimeType() === 'application/pdf') {
        $upload->setFile($file)->to('pdfs');
    } else {
        $upload->setFile($file)->to('other');
    }
}
```

## See also

- [Validation](Validation.md) · [Security Best Practices](Security-Best-Practices.md)
- [Local](Local-Adapter.md) · [FTP](FTP-Adapter.md) · [S3](S3-Adapter.md) adapters
- [Testing](Testing.md) — testing your own upload code
