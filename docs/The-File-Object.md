# The `File` Object

`InitPHP\Upload\File` is an immutable-ish value object describing one file you
want to store. It can wrap a temporary HTTP upload **or** any readable path on
disk. The **original client file name** — not the temporary path — is the
source of truth for the name and extension.

## Creating files

### `File::setPost(string $key): File[]`

Normalizes the `$_FILES` entry at `$key` into a list of `File` objects. It
handles both PHP form shapes:

```php
// <input type="file" name="avatar">      → single
// <input type="file" name="photos[]">    → multiple
$files = File::setPost('photos'); // always a File[]
```

Behaviour:

- Returns an **empty array** if the key is missing or the `$_FILES` entry is
  malformed (missing `name` / `tmp_name` / `size` / `type`).
- Entries reporting **`UPLOAD_ERR_NO_FILE`** (an empty file input) are
  **skipped** — you never get a phantom `File` for a field the user left blank.
- Entries that failed for another reason (e.g. `UPLOAD_ERR_INI_SIZE`) **are
  kept**; the error is exposed via [`getError()`](#geterror-int) /
  [`isValid()`](#isvalid-bool) and raised when the adapter validates the file.

```php
foreach (File::setPost('photos') as $file) {
    if (!$file->isValid()) {
        // e.g. the file exceeded upload_max_filesize
        continue;
    }
    $upload->setFile($file)->to();
}
```

### `File::setPath(string $path): File`

Wraps a single file that already exists on disk (an export, a generated PDF, a
seed asset, …).

```php
$file = File::setPath('/var/data/report.pdf');
$file->getName();      // "report.pdf"
$file->getExtension(); // "pdf"
```

Throws an [`UploadInvalidArgumentException`](Error-Handling.md#uploadinvalidargumentexception)
when `$path` is empty (or whitespace only). A path-loaded file is **copied** to
its destination by the local adapter, never moved.

### `new File(...)` — the constructor

You rarely need it directly, but it is public:

```php
new File(
    string  $path,          // source path (an upload tmp_name, or any path)
    ?string $name  = null,  // client name; defaults to basename($path)
    ?int    $size  = null,  // bytes; detected from $path when null
    ?string $type  = null,  // declared MIME; detected from $path when null
    int     $error = UPLOAD_ERR_OK
);
```

When `$size` or `$type` is omitted, `File` reads them from `$path` if it
exists, and falls back to `0` / `application/octet-stream` if it does not — it
never throws just because the path is unreadable.

## Accessors

| Method | Returns | Description |
| --- | --- | --- |
| [`getName()`](#getname-string) | `string` | Original client file name, e.g. `holiday.JPG`. |
| [`getExtension()`](#getextension-string) | `string` | Lower-cased extension without the dot, from the name. `''` if none. |
| [`getSize()`](#getsize-int) | `int` | Size in bytes (`0` if undeterminable). |
| [`getMimeType()`](#getmimetype-string) | `string` | The **declared** MIME type (client-supplied for uploads — untrusted). |
| [`getRealMimeType()`](#getrealmimetype-string) | `string` | The MIME type detected from the file's bytes with `finfo` (cached). |
| [`getPath()`](#getpath-string) | `string` | The raw source path as given to the constructor. |
| [`getRealPath()`](#getrealpath-string) | `string` | The resolved absolute path, falling back to the raw path. |
| [`getError()`](#geterror-int) | `int` | The `UPLOAD_ERR_*` code. |
| [`isValid()`](#isvalid-bool) | `bool` | `true` when the error code is `UPLOAD_ERR_OK`. |
| [`isUploaded()`](#isuploaded-bool) | `bool` | `true` when the source is a genuine HTTP POST upload. |
| [`getReName()`](#getrename-string) | `?string` | The rename target, or `null` if `rename()` was not called. |
| [`getURL()`](#geturl-string) | `?string` | The public URL, set by the adapter after a successful store. |
| [`rename()`](#renamestring-rename-self) | `self` | Set the stored name (extension re-attached automatically). |
| [`setURL()`](#seturlstring-url-self) | `self` | Set the public URL (adapters call this for you). |

### `getName(): string`

The original client file name.

```php
(new File('/tmp/phpA1B2', 'holiday.JPG'))->getName(); // "holiday.JPG"
```

### `getExtension(): string`

The lower-cased extension, derived from the **name**, with no leading dot.
Returns `''` when the name has no extension.

```php
(new File('/tmp/x', 'ARCHIVE.TAR.GZ'))->getExtension(); // "gz"
(new File('/tmp/x', 'README'))->getExtension();          // ""
```

> Deriving the extension from the name (not the temp path) is what makes
> [`allowed_extensions`](Validation.md) and [`rename()`](#renamestring-rename-self)
> work for real uploads, whose `tmp_name` has no extension.

### `getSize(): int`

The file size in bytes. `0` when it cannot be determined.

### `getMimeType(): string`

The **declared** MIME type. For uploads this is the browser-supplied value and
**must not be trusted** for security decisions. Use
[`getRealMimeType()`](#getrealmimetype-string) instead.

### `getRealMimeType(): string`

The MIME type detected from the file's actual content with the `fileinfo`
extension, falling back to the declared type when detection fails. The result
is cached after the first call.

```php
$file = new File('/path/cat.png', 'cat.png', null, 'image/png');
$file->getRealMimeType(); // "image/png" — read from the bytes
```

This is the value [validation](Validation.md) checks against
`allowed_mime_types`.

### `getPath(): string`

The raw source path exactly as passed to the constructor (an upload `tmp_name`,
or your path).

### `getRealPath(): string`

The canonical absolute path (`realpath()`), falling back to the raw path when
it cannot be resolved — so it never returns `false`.

### `getError(): int`

The PHP [`UPLOAD_ERR_*`](https://www.php.net/manual/en/features.file-upload.errors.php)
code associated with the file. `UPLOAD_ERR_OK` (0) for path-loaded files.

### `isValid(): bool`

`true` when [`getError()`](#geterror-int) is `UPLOAD_ERR_OK`. The adapter
rejects an invalid file before any other check.

### `isUploaded(): bool`

`true` when the source path is a genuine HTTP POST upload (`is_uploaded_file()`).
Adapters use this to choose between moving and copying.

### `getReName(): ?string`

The target name set by [`rename()`](#renamestring-rename-self), or `null`.

### `getURL(): ?string`

The public URL of the stored file, or `null` until a successful `to()`.

### `rename(string $rename): self`

Sets the name the file should be stored as, automatically re-attaching the
original extension:

```php
$file = File::setPath('/tmp/IMG_4821.JPG'); // extension "jpg"
$file->rename('profile');
$file->getReName(); // "profile.jpg"
```

A name with no original extension stays bare:

```php
(new File('/tmp/x', 'LICENSE'))->rename('COPYING')->getReName(); // "COPYING"
```

The renamed value is what `to()` stores (with any `$target` prefix in front).

### `setURL(string $url): self`

Stores the public URL. Adapters call this for you after a successful transfer;
you normally only **read** it with `getURL()`.

## Declared vs. real MIME type

| Method | Source | Trust |
| --- | --- | --- |
| `getMimeType()` | the client's `Content-Type` header | **untrusted** — trivially spoofed |
| `getRealMimeType()` | the file's bytes via `finfo` | trustworthy |

Always validate against the real type. See
[Security Best Practices](Security-Best-Practices.md).

## See also

- [Validation](Validation.md) — how these values are checked
- [Core Concepts](Core-Concepts.md) — where `File` fits in the bigger picture
- [API Reference](API-Reference.md#file) — the condensed signature list
