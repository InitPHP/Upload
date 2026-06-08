# The `File` object

`InitPHP\Upload\File` is an immutable-ish value object describing one file you
want to store. The **original client file name** — not the temporary upload
path — is the source of truth for the name and extension.

## Creating files

### `File::setPost(string $key): File[]`

Normalizes a `$_FILES` entry into a list of `File` objects. Works for both the
single-file shape (`<input type="file" name="avatar">`) and the multi-file
shape (`<input type="file" name="photos[]" multiple>`).

```php
$files = File::setPost('photos'); // File[]
```

- Returns an empty array if the key is missing or malformed.
- Entries with `UPLOAD_ERR_NO_FILE` (empty inputs) are skipped.
- Entries that failed for another reason are kept; the error is reported by
  `getError()` / `isValid()` and raised when the adapter validates the file.

### `File::setPath(string $path): File`

Wraps a file that already exists on disk.

```php
$file = File::setPath('/var/data/report.pdf');
```

Throws an `UploadInvalidArgumentException` if `$path` is empty.

### `new File(...)`

You rarely need the constructor directly, but it is public:

```php
new File(
    string $path,          // source path (an upload tmp_name or any path)
    ?string $name = null,  // client name; defaults to basename($path)
    ?int $size = null,     // bytes; detected from $path when null
    ?string $type = null,  // declared MIME; detected from $path when null
    int $error = UPLOAD_ERR_OK
);
```

## Accessors

| Method | Returns | Description |
| ------ | ------- | ----------- |
| `getName()` | `string` | The original client file name, e.g. `holiday.JPG`. |
| `getExtension()` | `string` | Lower-cased extension without the dot (from the name), or `''`. |
| `getSize()` | `int` | Size in bytes (`0` if it could not be determined). |
| `getMimeType()` | `string` | The **declared** MIME type — client-supplied for uploads, so untrusted. |
| `getRealMimeType()` | `string` | The MIME type detected from the file contents with `finfo` (cached). |
| `getPath()` | `string` | The raw source path as given to the constructor. |
| `getRealPath()` | `string` | The resolved absolute path, falling back to the raw path. |
| `getError()` | `int` | The `UPLOAD_ERR_*` code. |
| `isValid()` | `bool` | `true` when the error code is `UPLOAD_ERR_OK`. |
| `isUploaded()` | `bool` | `true` when the source is a genuine HTTP POST upload. |
| `getReName()` | `?string` | The rename target, or `null` if `rename()` was not called. |
| `getURL()` | `?string` | The public URL, set by the adapter after a successful store. |

## Renaming

`rename()` sets the stored name and re-attaches the original extension
automatically:

```php
$file = File::setPath('/tmp/IMG_4821.JPG');
$file->getExtension();       // "jpg"

$file->rename('profile');
$file->getReName();          // "profile.jpg"
```

The new name is used by `to()` instead of the original one. A name with no
extension stays bare:

```php
(new File('/tmp/x', 'LICENSE'))->rename('COPYING')->getReName(); // "COPYING"
```

## Why the extension comes from the name

For a real upload, the source path is a temporary file such as
`/tmp/phpA1B2C3` with **no extension**. Deriving the extension from the original
client name (`holiday.JPG` → `jpg`) is what makes
[extension validation](validation.md) and renaming work correctly.

## Trusted vs. declared MIME type

`getMimeType()` returns whatever the client claimed and can be spoofed.
`getRealMimeType()` inspects the file's actual bytes with `finfo`. Validation
always uses the real type — prefer it in your own checks too.
