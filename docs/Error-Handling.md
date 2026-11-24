# Error Handling

The package throws three exception types, all under
`InitPHP\Upload\Exceptions`.

```
\RuntimeException
 └── UploadException
      └── UnsupportedException

\InvalidArgumentException
 └── UploadInvalidArgumentException
```

| Class | Extends | Meaning |
| --- | --- | --- |
| `UploadException` | `\RuntimeException` | A file could not be validated or stored. The base type for runtime failures. |
| `UnsupportedException` | `UploadException` | An adapter cannot run here — a required extension (`ext-ftp`) or dependency (`aws/aws-sdk-php`) is missing. |
| `UploadInvalidArgumentException` | `\InvalidArgumentException` | A programmer error in the arguments, e.g. `File::setPath('')`. |

Because `UnsupportedException` **extends** `UploadException`, a single
`catch (UploadException $e)` handles both runtime and unsupported-environment
failures. `UploadInvalidArgumentException` sits in the SPL
`InvalidArgumentException` hierarchy instead, marking misuse of the API rather
than a runtime upload failure.

## `UploadException`

Thrown by `to()` when:

- no file was set with `setFile()`;
- the file's upload error code is not `UPLOAD_ERR_OK`;
- a [validation](Validation.md) rule (extension, MIME type, size) is violated;
- the backend fails — the destination directory cannot be created, an FTP
  connection or login fails, the S3 SDK errors, and so on. Backend errors are
  **wrapped**, preserving the original throwable as `$e->getPrevious()`.

```php
use InitPHP\Upload\Exceptions\UploadException;

try {
    $stored = $upload->setFile($file)->to('avatars');

    if ($stored === false) {
        // The write itself returned false (not an exception).
        $error = 'The file could not be stored.';
    }
} catch (UploadException $e) {
    $error = $e->getMessage();
    $cause = $e->getPrevious(); // the underlying error, when one was wrapped
}
```

### `to()` returns `false` vs. throws

| Outcome | How it is reported |
| --- | --- |
| Validation failed (extension/MIME/size/upload error) | **throws** `UploadException` |
| Backend connection/auth/SDK error | **throws** `UploadException` |
| The transfer call returned false (e.g. write failed, no `ObjectURL`) | **returns** `false` |
| Success | returns the stored `File` |

So a robust handler checks **both**: catch `UploadException`, *and* test for
`=== false`.

## `UnsupportedException`

Thrown by an **adapter's constructor** when its environment is missing:

```php
use InitPHP\Upload\Adapters\FTPAdapter;
use InitPHP\Upload\Exceptions\UnsupportedException;

try {
    $adapter = new FTPAdapter($credentials, $options); // $options: your allow-lists, see Validation
} catch (UnsupportedException $e) {
    // ext-ftp is not installed: "FTP adapter cannot be used on your server."
}
```

| Adapter | Missing requirement | Message |
| --- | --- | --- |
| `FTPAdapter` | `ext-ftp` | `FTP adapter cannot be used on your server.` |
| `S3Adapter` | `aws/aws-sdk-php` | `AWS S3 SDK must be installed to use this adapter. Try running "composer require aws/aws-sdk-php".` |

## `UploadInvalidArgumentException`

Thrown for invalid arguments — currently an empty path passed to
`File::setPath()`:

```php
use InitPHP\Upload\File;
use InitPHP\Upload\Exceptions\UploadInvalidArgumentException;

try {
    File::setPath('');
} catch (UploadInvalidArgumentException $e) {
    // "The file path cannot be empty."
}
```

This is a *programmer* error (you passed a bad argument), which is why it is an
`InvalidArgumentException` and **not** an `UploadException`.

## Catching everything from one call

```php
use InitPHP\Upload\Exceptions\UploadException;

function store(\InitPHP\Upload\Upload $upload, \InitPHP\Upload\File $file): ?string
{
    try {
        $stored = $upload->setFile($file)->to('uploads');

        return $stored === false ? null : $stored->getURL();
    } catch (UploadException $e) {
        // validation, unsupported environment, or a backend failure
        error_log('Upload failed: ' . $e->getMessage());

        return null;
    }
}
```

## See also

- [Validation](Validation.md) — what each validation message means
- [Troubleshooting](Troubleshooting.md) — fixing the most common errors
- [API Reference](API-Reference.md#exceptions) — the condensed table
