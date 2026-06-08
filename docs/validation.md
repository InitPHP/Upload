# Validation

Every adapter accepts the same validation **options** as its second constructor
argument. They are checked, in order, the moment you call `to()`. A violation
throws an [`UploadException`](exceptions.md); it does **not** return `false`.

```php
$options = [
    'allowed_extensions' => ['jpg', 'jpeg', 'png'],
    'allowed_mime_types' => ['image/jpeg', 'image/png'],
    'allowed_max_size'   => 2 * 1024 * 1024, // 2 MB in bytes
];

$adapter = new LocalAdapter($credentials, $options);
```

An empty array or `0` means "no restriction" for that option. With no options
at all, any file that uploaded without error is accepted.

## The checks

| Option | Type | How it is checked |
| ------ | ---- | ----------------- |
| `allowed_extensions` | `string[]` | The file's extension (from its original name) must be in the list. The comparison is **case-insensitive**, so `['jpg']` accepts `Photo.JPG`. |
| `allowed_mime_types` | `string[]` | The file's **real** MIME type — detected from its bytes with `finfo`, not the client-supplied header — must be in the list. |
| `allowed_max_size` | `int` | The file size in **bytes** must not exceed this value. |

The upload error code is checked first: a file whose `getError()` is not
`UPLOAD_ERR_OK` is rejected before any option is considered.

## Messages

| Situation | Exception message |
| --------- | ----------------- |
| No file was set with `setFile()` | `The file to be uploaded is undefined.` |
| The upload errored (`UPLOAD_ERR_*`) | `The file upload failed with error code N.` |
| Extension not allowed | `This file extension is not allowed.` |
| MIME type not allowed | `This file type is not allowed.` |
| Larger than the limit | `Exceeds the maximum uploadable file size.` |

## Example: catch and report

```php
use InitPHP\Upload\Exceptions\UploadException;

try {
    $stored = $upload->setFile($file)->to();
    if ($stored === false) {
        $error = 'The file could not be written.';
    }
} catch (UploadException $e) {
    $error = $e->getMessage(); // e.g. "This file extension is not allowed."
}
```

## Why the real MIME type matters

`allowed_mime_types` deliberately ignores the browser-supplied `type` field,
which is trivial to forge. It uses `finfo` (the `fileinfo` extension) to read
the file's actual content type, so renaming `malware.php` to `cat.png` will not
slip past a `['image/png']` allow-list. Pair `allowed_mime_types` with
`allowed_extensions` for the strongest check.
