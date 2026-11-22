# API Reference

Every public member of `initphp/upload`. For narrative explanations, follow the
links to the dedicated pages.

- [`File`](#file)
- [`Upload`](#upload)
- [`UploadAdapterInterface`](#uploadadapterinterface)
- [`BaseUploadAdapter`](#baseuploadadapter)
- [`LocalAdapter`](#localadapter) · [`FTPAdapter`](#ftpadapter) · [`S3Adapter`](#s3adapter)
- [Exceptions](#exceptions)
- [Validation options](#validation-options)

---

## `File`

`InitPHP\Upload\File` — `final` value object describing one file.
Full guide: [The `File` Object](The-File-Object.md).

### Static constructors

```php
public static function setPost(string $key): File[]
public static function setPath(string $path): File   // throws UploadInvalidArgumentException on empty path
```

### Constructor

```php
public function __construct(
    string  $path,
    ?string $name  = null,   // defaults to basename($path)
    ?int    $size  = null,   // detected from $path when null, else 0
    ?string $type  = null,   // detected from $path when null, else application/octet-stream
    int     $error = UPLOAD_ERR_OK
)
```

### Methods

| Signature | Description |
| --- | --- |
| `getName(): string` | Original client file name. |
| `getExtension(): string` | Lower-cased extension from the name, no dot; `''` if none. |
| `getSize(): int` | Size in bytes (`0` if unknown). |
| `getMimeType(): string` | Declared (client) MIME type — untrusted. |
| `getRealMimeType(): string` | Real MIME type via `finfo` (cached). |
| `getPath(): string` | Raw source path. |
| `getRealPath(): string` | Resolved absolute path; falls back to the raw path. |
| `getError(): int` | `UPLOAD_ERR_*` code. |
| `isValid(): bool` | `true` when the error code is `UPLOAD_ERR_OK`. |
| `isUploaded(): bool` | `true` for a genuine HTTP POST upload. |
| `rename(string $rename): self` | Set the stored name; the original extension is re-attached. |
| `getReName(): ?string` | The rename target, or `null`. |
| `setURL(string $url): self` | Set the public URL (adapters call this). |
| `getURL(): ?string` | The public URL, or `null` before a successful store. |

---

## `Upload`

`InitPHP\Upload\Upload` — `final`, `implements UploadAdapterInterface`. A
type-safe decorator over an adapter. Full guide:
[Core Concepts](Core-Concepts.md#3-upload--the-thing-you-call).

```php
public function __construct(BaseUploadAdapter $adapter)
public function getAdapter(): BaseUploadAdapter
```

It forwards the whole [`UploadAdapterInterface`](#uploadadapterinterface):

| Signature | Returns | Notes |
| --- | --- | --- |
| `setFile(File $file): self` | the **same** `Upload` | queue the file to store |
| `setOption(string $name, $value): self` | the **same** `Upload` | set one option |
| `setOptions(array $options): self` | the **same** `Upload` | merge options |
| `setCredentials(array $credentials): self` | the **same** `Upload` | merge credentials |
| `withOption(string $name, $value): self` | a **new** `Upload` | option on a fresh clone |
| `withOptions(array $options): self` | a **new** `Upload` | options on a fresh clone |
| `withCredentials(array $credentials): self` | a **new** `Upload` | credentials on a fresh clone |
| `with(): self` | a **new** `Upload` | wraps a fresh adapter clone |
| `to($target = null)` | `File\|false` | validate and store |

---

## `UploadAdapterInterface`

`InitPHP\Upload\Interfaces\UploadAdapterInterface` — the contract every adapter
(and `Upload`) implements.

```php
public function with(): self;
public function setOption(string $name, $value): self;
public function setOptions(array $options): self;
public function withOption(string $name, $value): self;
public function withOptions(array $options): self;
public function setCredentials(array $credentials): self;
public function withCredentials(array $credentials): self;
public function setFile(File $file): self;
public function to($target = null);          // File|false
```

- **`set*`** methods mutate the current instance and return it.
- **`with*`** methods clone first (releasing any open connection/client) and
  return the copy, leaving the original untouched.
- **`to($target)`** validates and stores; `$target` is a destination path/key
  prefix. Returns the stored `File`, or `false` on a non-exceptional write
  failure; throws [`UploadException`](Error-Handling.md) on validation/transfer
  errors.

---

## `BaseUploadAdapter`

`InitPHP\Upload\Adapters\BaseUploadAdapter` — `abstract`, implements the
interface and provides the shared behaviour. You extend it only to add a new
backend.

```php
public function __construct(array $credentials, array $options)
abstract public function with(): self;
abstract public function to($target = null);
```

Default options merged in by the constructor:

```php
['allowed_extensions' => [], 'allowed_mime_types' => [], 'allowed_max_size' => 0]
```

Protected helpers available to subclasses: `checkFile(): void` (runs
validation), `targetName($target): string` (builds the prefixed name), and the
typed credential/option readers `stringCredential()`, `intCredential()`,
`boolCredential()`, `arrayOption()`, `intOption()`.

---

## `LocalAdapter`

`InitPHP\Upload\Adapters\LocalAdapter extends BaseUploadAdapter`. Full guide:
[Local Adapter](Local-Adapter.md).

```php
public function __construct(array $credentials, array $options = [])
```

| Credential | Default | Description |
| --- | --- | --- |
| `dir` | `''` | Base directory files are written into. |
| `url` | `''` | Public base URL for building `getURL()`. |

---

## `FTPAdapter`

`InitPHP\Upload\Adapters\FTPAdapter extends BaseUploadAdapter`. Requires
`ext-ftp`. Full guide: [FTP Adapter](FTP-Adapter.md).

```php
public function __construct(array $credentials, array $options = [])
// throws UnsupportedException if ext-ftp is missing
```

| Credential | Default | Description |
| --- | --- | --- |
| `host` | `''` | FTP host. |
| `port` | `21` | FTP port. |
| `username` | `''` | Login user. |
| `password` | `''` | Login password. |
| `timeout` | `90` | Timeout (seconds). |
| `url` | `''` | Public base URL. |
| `passive` | `true` | Passive mode. |
| `ssl` | `false` | Explicit FTPS. |

---

## `S3Adapter`

`InitPHP\Upload\Adapters\S3Adapter extends BaseUploadAdapter`. Requires
`aws/aws-sdk-php`. Full guide: [S3 Adapter](S3-Adapter.md).

```php
public function __construct(array $credentials, array $options = [])
// throws UnsupportedException if aws/aws-sdk-php is missing
```

| Credential | Default | Description |
| --- | --- | --- |
| `key` | `''` | AWS access key ID. |
| `secret_key` | `''` | AWS secret access key. |
| `region` | `''` | Bucket region. |
| `bucket` | `''` | Target bucket. |
| `ACL` | `public-read` | Canned object ACL. |
| `version` | `latest` | S3 API version. |

---

## Exceptions

Namespace `InitPHP\Upload\Exceptions`. Full guide:
[Error Handling](Error-Handling.md).

| Class | Extends | Thrown when |
| --- | --- | --- |
| `UploadException` | `\RuntimeException` | Validation or storage fails. |
| `UnsupportedException` | `UploadException` | A required extension/dependency is missing. |
| `UploadInvalidArgumentException` | `\InvalidArgumentException` | A bad argument, e.g. `File::setPath('')`. |

---

## Validation options

Passed as the second constructor argument of any adapter. Full guide:
[Validation](Validation.md).

| Option | Type | Default | Meaning |
| --- | --- | --- | --- |
| `allowed_extensions` | `string[]` | `[]` | Allowed extensions (case-insensitive). |
| `allowed_mime_types` | `string[]` | `[]` | Allowed **real** MIME types. |
| `allowed_max_size` | `int` | `0` | Maximum size in bytes. |
