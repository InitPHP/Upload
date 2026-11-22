# Local Adapter

`InitPHP\Upload\Adapters\LocalAdapter` stores files on the local filesystem.
It is the simplest adapter and needs no extensions beyond `ext-fileinfo`.

## Credentials

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `dir` | `string` | `''` | Absolute base directory files are written into. |
| `url` | `string` | `''` | Public base URL the directory is served from; used to build [`getURL()`](The-File-Object.md#geturl-string). |

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\LocalAdapter;

$adapter = new LocalAdapter(
    [
        'dir' => __DIR__ . '/uploads/',
        'url' => 'https://example.com/uploads/',
    ],
    [
        'allowed_extensions' => ['jpg', 'png', 'pdf'],
        'allowed_max_size'   => 5 * 1024 * 1024,
    ]
);

$upload = new Upload($adapter);
```

`dir` should usually point **outside the web root** — see
[Security Best Practices](Security-Best-Practices.md#2-keep-uploads-out-of-the-web-root).

## Storing files

```php
foreach (File::setPost('documents') as $file) {
    $stored = $upload->setFile($file)->to();

    if ($stored !== false) {
        echo $stored->getURL(); // https://example.com/uploads/<name>
    }
}
```

## Sub-directories

The `$target` argument is a path prefix appended under `dir`. Missing
directories are created automatically and **recursively**:

```php
$upload->setFile($file)->to('invoices/2026/06');
// file: <dir>/invoices/2026/06/<name>
// URL:  https://example.com/uploads/invoices/2026/06/<name>
```

## Move vs. copy

The adapter chooses how to place the file based on its source:

| Source of the `File` | Action |
| --- | --- |
| A real HTTP upload (`File::setPost()`) | **moved** with `move_uploaded_file()` |
| A path-loaded file (`File::setPath()`) | **copied** (the original stays put) |

```php
$file = File::setPath('/var/assets/logo.png');
$upload->setFile($file)->to();
// /var/assets/logo.png still exists; a copy now lives under `dir`.
```

This distinction matters: `move_uploaded_file()` only works on genuine POST
uploads, so copying is the correct behaviour for files you loaded yourself.

## Renaming

```php
$file = File::setPost('avatar')[0];
$file->rename('user-' . $userId);          // keeps the original extension
$stored = $upload->setFile($file)->to('avatars');
// stored at <dir>/avatars/user-42.jpg
```

## Return value and errors

| Outcome | Result |
| --- | --- |
| Stored successfully | the [`File`](The-File-Object.md), with `getURL()` set |
| The move/copy returned false (e.g. not writable) | `false` |
| Validation failed | throws [`UploadException`](Error-Handling.md) |
| Destination directory could not be created | throws `UploadException` |

```php
use InitPHP\Upload\Exceptions\UploadException;

try {
    $result = $upload->setFile($file)->to();
    $ok = $result !== false;
} catch (UploadException $e) {
    // validation failed, or the directory could not be created
    $message = $e->getMessage();
}
```

## Tips

- **Permissions.** The PHP process must be able to write to `dir`. Newly
  created sub-directories are made with mode `0777` (subject to your umask).
- **Trailing slashes** in `dir` and `url` are handled for you — `'/uploads'`
  and `'/uploads/'` behave identically.
- **Serving files.** `url` is only used to compute the public URL string; the
  package does not serve the files. Point `url` at wherever your web server
  exposes `dir`.

## See also

- [Validation](Validation.md) · [Security Best Practices](Security-Best-Practices.md)
- [Recipes](Recipes.md) — unique names, avatars, galleries
- [FTP Adapter](FTP-Adapter.md) · [S3 Adapter](S3-Adapter.md)
