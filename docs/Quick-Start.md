# Quick Start

Five minutes from `composer require` to a stored, validated upload.

## 1. The HTML form

```html
<form method="post" action="/upload.php" enctype="multipart/form-data">
    <input type="file" name="photos[]" multiple>
    <button type="submit">Upload</button>
</form>
```

The `enctype="multipart/form-data"` is required for file uploads. The `[]` and
`multiple` make this a multi-file field — but the PHP side is identical for a
single-file `name="photo"` field.

## 2. Build an adapter

An **adapter** knows *where* files go (`credentials`) and *what* is allowed
(`options`, optional):

```php
<?php
declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';

use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\LocalAdapter;

$adapter = new LocalAdapter(
    [   // credentials — where files go
        'dir' => __DIR__ . '/uploads/',
        'url' => 'https://example.com/uploads/',
    ],
    [   // options — what is allowed (optional)
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_max_size'   => 2 * 1024 * 1024, // 2 MB, in bytes
    ]
);

$upload = new Upload($adapter);
```

## 3. Read the uploaded files

`File::setPost()` reads a `$_FILES` key and always returns a `File[]`, so one
loop handles both single- and multi-file fields:

```php
$files = File::setPost('photos'); // File[]
```

Empty file inputs are skipped automatically. See
[The `File` Object](The-File-Object.md) for the details.

## 4. Store them

```php
$results = [];

foreach (File::setPost('photos') as $file) {
    try {
        $stored = $upload->setFile($file)->to();

        if ($stored === false) {
            $results[] = ['name' => $file->getName(), 'error' => 'write failed'];
            continue;
        }

        $results[] = ['name' => $file->getName(), 'url' => $stored->getURL()];
    } catch (\InitPHP\Upload\Exceptions\UploadException $e) {
        // validation failed (extension, MIME, size) or a backend error
        $results[] = ['name' => $file->getName(), 'error' => $e->getMessage()];
    }
}

header('Content-Type: application/json');
echo json_encode($results);
```

`to()` returns the stored [`File`](The-File-Object.md) (with its public URL set)
on success, or `false` if the underlying write failed. Validation problems are
thrown as an [`UploadException`](Error-Handling.md), not returned as `false`.

## 5. Store under a sub-folder

The optional `$target` is a destination path/key prefix:

```php
$upload->setFile($file)->to('2026/06');
// stored at <dir>/2026/06/<name>
// URL:      https://example.com/uploads/2026/06/<name>
```

Missing directories are created for you. `$target` means the same thing in
every adapter — see [Core Concepts](Core-Concepts.md#4-target--the-destination-prefix).

## 6. Switch the backend

Only the adapter changes; steps 3–5 stay exactly the same.

```php
use InitPHP\Upload\Adapters\FTPAdapter;

$upload = new Upload(new FTPAdapter([
    'host'     => 'ftp.example.com',
    'username' => 'user',
    'password' => 'secret',
    'url'      => 'https://cdn.example.com/',
]));
```

## What just happened

1. `File::setPost('photos')` normalized `$_FILES['photos']` into `File`
   objects, skipping empty inputs and taking the **original file name** (and
   therefore the extension) from each upload.
2. `to()` validated the file against your options — checking the extension,
   the **real** MIME type (via `finfo`), and the size — then moved the upload
   into place and set its public URL.
3. You got back the stored `File`, or a thrown
   [`UploadException`](Error-Handling.md) explaining what went wrong.

## Next steps

- [Core Concepts](Core-Concepts.md) — `File`, adapters, the `Upload` decorator,
  and `$target`
- [Validation](Validation.md) — the full rules for extensions, MIME types and size
- [Security Best Practices](Security-Best-Practices.md) — before you go live
- [Recipes](Recipes.md) — avatars, galleries, unique names, framework glue
