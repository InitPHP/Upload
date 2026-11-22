# Core Concepts

The package has four moving parts. Once you understand how they fit together,
every adapter behaves the same way.

```
File  ──setFile()──▶  Upload  ──delegates──▶  Adapter  ──▶  storage backend
 ▲                                              │
 │                                              ▼
$_FILES / a path                         validate + store
```

## 1. `File` — what you are uploading

A [`File`](The-File-Object.md) is a value object describing **one** file. You
rarely build it by hand; instead:

- [`File::setPost('field')`](The-File-Object.md#filesetpoststring-key-file) reads
  `$_FILES['field']` and returns a `File[]` — one entry per uploaded file.
- [`File::setPath('/path/to/file')`](The-File-Object.md#filesetpathstring-path-file)
  wraps a file already on disk.

The crucial detail: the **original client file name** is the source of truth
for the name and the extension — not the temporary upload path, which has no
extension. This is what makes extension validation and renaming work.

```php
$file = File::setPath('/tmp/IMG_0042.JPG');
$file->getName();      // "IMG_0042.JPG"
$file->getExtension(); // "jpg"  (lower-cased)
$file->getSize();      // bytes
```

## 2. Adapter — where it goes

An **adapter** is a storage backend. All three implement the same
[`UploadAdapterInterface`](API-Reference.md#uploadadapterinterface):

| Adapter | Stores to | Page |
| --- | --- | --- |
| `LocalAdapter` | the local filesystem | [Local Adapter](Local-Adapter.md) |
| `FTPAdapter` | an FTP/FTPS server | [FTP Adapter](FTP-Adapter.md) |
| `S3Adapter` | an Amazon S3 bucket | [S3 Adapter](S3-Adapter.md) |

Each takes two arrays:

```php
new LocalAdapter($credentials, $options);
//               └ where         └ what is allowed (optional)
```

- **`credentials`** — backend-specific connection details (a directory and
  URL for local; host/user/password for FTP; key/secret/bucket for S3).
- **`options`** — the [validation](Validation.md) rules, identical across
  adapters.

## 3. `Upload` — the thing you call

[`Upload`](API-Reference.md#upload) is a thin, type-safe **decorator** around an
adapter. It is what you call `setFile()` and `to()` on:

```php
$upload = new Upload($adapter);

$upload->setFile($file)   // returns the same Upload (chainable)
       ->to();            // returns File|false
```

Why wrap the adapter at all? `Upload` implements the same interface, gives you
a stable type to type-hint against, and keeps the fluent API consistent:

- The mutating **`set*`** methods (`setFile`, `setOption`, `setCredentials`, …)
  return the **same** `Upload` for chaining.
- The **`with*`** methods (`withOption`, `withCredentials`, …) return a **new**
  `Upload` wrapping a fresh adapter clone, leaving the original untouched —
  handy for per-request tweaks.

```php
$base = new Upload($adapter);

// Original unchanged; $strict is an independent copy.
$strict = $base->withOptions(['allowed_max_size' => 512 * 1024]);
```

## 4. `$target` — the destination prefix

`to($target)` takes an optional `$target` that means the **same thing in every
adapter**: a destination path/key **prefix**.

```php
$upload->setFile($file)->to('avatars/2026');
```

| Adapter | Result of `to('avatars/2026')` |
| --- | --- |
| Local | file written to `<dir>/avatars/2026/<name>` |
| FTP | file sent to `<remote>/avatars/2026/<name>` (dirs created) |
| S3 | object stored under key `avatars/2026/<name>` in the bucket |

Call `to()` with no argument to store at the destination root. Backslashes and
surrounding slashes in `$target` are normalized, so `'\\a\\b\\'` and `'a/b'`
behave identically.

> **S3 note:** `$target` is the object **key prefix**, never the bucket. The
> bucket always comes from the credentials. (Earlier versions treated it as the
> bucket name — see the [changelog](https://github.com/InitPHP/Upload/blob/main/CHANGELOG.md).)

## Move vs. copy

How the file reaches its destination depends on where it came from:

| Source | Local adapter behaviour |
| --- | --- |
| A real HTTP upload (`File::setPost()`) | **moved** with `move_uploaded_file()` |
| A path-loaded file (`File::setPath()`) | **copied**, leaving the original in place |

The adapter decides via [`File::isUploaded()`](The-File-Object.md). This means you
can safely re-upload an existing asset with `setPath()` without destroying it.

## The lifecycle of an upload

```php
$upload->setFile($file)->to('avatars');
```

1. **Validate** — the adapter checks the upload error code, then your
   `allowed_extensions`, `allowed_mime_types` and `allowed_max_size`. A failure
   throws an [`UploadException`](Error-Handling.md).
2. **Resolve the name** — `$target` prefix + the renamed name (if you called
   `rename()`) or the original name.
3. **Store** — move/copy/transfer/put the bytes, creating directories as
   needed.
4. **Set the URL** — on success the stored `File` gets its public
   [`getURL()`](The-File-Object.md) populated, and is returned. A failed write
   returns `false`.

## Next steps

- [The `File` Object](The-File-Object.md) — every accessor in detail
- [Validation](Validation.md) — the rules and their messages
- [Local](Local-Adapter.md) / [FTP](FTP-Adapter.md) / [S3](S3-Adapter.md) adapters
- [API Reference](API-Reference.md) — the complete public surface
