# Exceptions

The package throws three exception types from the
`InitPHP\Upload\Exceptions` namespace.

```
\RuntimeException
 └── UploadException
      └── UnsupportedException

\InvalidArgumentException
 └── UploadInvalidArgumentException
```

| Class | Extends | Meaning |
| ----- | ------- | ------- |
| `UploadException` | `\RuntimeException` | A file could not be validated or stored. The base type for runtime failures. |
| `UnsupportedException` | `UploadException` | An adapter cannot run here — a required extension (`ext-ftp`) or dependency (`aws/aws-sdk-php`) is missing. |
| `UploadInvalidArgumentException` | `\InvalidArgumentException` | A programmer error in the arguments, e.g. `File::setPath('')`. |

Because `UnsupportedException` extends `UploadException`, a single
`catch (UploadException $e)` handles both runtime and unsupported-environment
failures. `UploadInvalidArgumentException` sits in the SPL
`InvalidArgumentException` hierarchy instead, signalling misuse of the API
rather than a runtime upload failure.

## When each is thrown

### `UploadException`

Raised by `to()` when:

- no file was set with `setFile()`;
- the file's upload error code is not `UPLOAD_ERR_OK`;
- a [validation](validation.md) restriction (extension, MIME type, size) is
  violated;
- the storage backend fails (directory cannot be created, FTP connection or
  login fails, the S3 SDK errors, …). Backend errors are wrapped, preserving
  the original throwable as `$e->getPrevious()`.

### `UnsupportedException`

Raised by an adapter's constructor when its environment is missing:

```php
use InitPHP\Upload\Adapters\FTPAdapter;
use InitPHP\Upload\Exceptions\UnsupportedException;

try {
    $adapter = new FTPAdapter($credentials);
} catch (UnsupportedException $e) {
    // ext-ftp is not installed
}
```

### `UploadInvalidArgumentException`

Raised for invalid arguments, currently an empty path:

```php
use InitPHP\Upload\File;
use InitPHP\Upload\Exceptions\UploadInvalidArgumentException;

try {
    File::setPath('');
} catch (UploadInvalidArgumentException $e) {
    // "The file path cannot be empty."
}
```

## Catching everything from one adapter

```php
use InitPHP\Upload\Exceptions\UploadException;

try {
    $stored = $upload->setFile($file)->to('avatars');
    $ok = $stored !== false;
} catch (UploadException $e) {
    // validation, unsupported environment, or a backend failure
    $message = $e->getMessage();
    $cause   = $e->getPrevious(); // the underlying error, when wrapped
}
```
