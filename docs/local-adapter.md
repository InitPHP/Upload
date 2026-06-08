# Local adapter

`InitPHP\Upload\Adapters\LocalAdapter` stores files on the local filesystem.

## Credentials

| Key | Type | Description |
| --- | ---- | ----------- |
| `dir` | `string` | Absolute base directory files are written into. |
| `url` | `string` | Public base URL the directory is served from, used to build `getURL()`. |

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\LocalAdapter;

$adapter = new LocalAdapter([
    'dir' => __DIR__ . '/uploads/',
    'url' => 'https://example.com/uploads/',
], [
    'allowed_extensions' => ['jpg', 'png', 'pdf'],
    'allowed_max_size'   => 5 * 1024 * 1024,
]);

$upload = new Upload($adapter);
```

## Storing files

```php
foreach (File::setPost('documents') as $file) {
    $stored = $upload->setFile($file)->to();

    if ($stored !== false) {
        echo $stored->getURL(); // https://example.com/uploads/<name>
    }
}
```

### Sub-directories

The `$target` argument is a path prefix appended under `dir`. Missing
directories are created automatically (recursively).

```php
$upload->setFile($file)->to('invoices/2026');
// stored at <dir>/invoices/2026/<name>
// URL:      https://example.com/uploads/invoices/2026/<name>
```

## Move vs. copy

- A genuine HTTP upload (loaded via `File::setPost()`) is relocated with
  `move_uploaded_file()`.
- A file loaded with `File::setPath()` is **copied**, so the original file is
  left untouched.

```php
$file = File::setPath('/var/assets/logo.png');
$upload->setFile($file)->to();
// /var/assets/logo.png still exists; a copy now lives under `dir`.
```

## Return value and errors

- `to()` returns the stored `File` on success, with `getURL()` populated.
- It returns `false` if the move/copy itself failed (for example, the
  destination is not writable).
- It throws an [`UploadException`](exceptions.md) when validation fails or the
  destination directory cannot be created.

```php
use InitPHP\Upload\Exceptions\UploadException;

try {
    $result = $upload->setFile($file)->to();
    $ok = $result !== false;
} catch (UploadException $e) {
    // validation failed or the directory could not be created
}
```
