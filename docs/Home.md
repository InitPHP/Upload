# InitPHP Upload — Wiki

Welcome to the official documentation for **`initphp/upload`** — a small,
adapter-based file-upload library for **PHP 8.2+**.

It validates an uploaded (or local) file and stores it on **local disk**, an
**FTP/FTPS server**, or **Amazon S3** through one consistent API. Swap the
backend without touching your upload code.

```bash
composer require initphp/upload
```

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\LocalAdapter;

$upload = new Upload(new LocalAdapter([
    'dir' => '/var/app/storage/uploads/',        // outside the web root
    'url' => 'https://example.com/uploads/',     // served read-only, scripts never executed
], [
    'allowed_extensions' => ['jpg', 'png', 'webp'],
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'], // real type, via finfo
    'allowed_max_size'   => 2 * 1024 * 1024, // 2 MB
]));

foreach (File::setPost('photos') as $file) {
    $file->rename(bin2hex(random_bytes(16))); // never keep the client's file name

    $stored = $upload->setFile($file)->to();

    if ($stored !== false) {
        echo htmlspecialchars((string) $stored->getURL(), ENT_QUOTES, 'UTF-8'); // https://example.com/uploads/<32hex>.jpg
    }
}
```

## The package in one table

| Symbol | Role |
| --- | --- |
| [`File`](The-File-Object.md) | A value object describing one file to upload. Build a list from `$_FILES` with `File::setPost()`, or wrap a path with `File::setPath()`. |
| [`Upload`](API-Reference.md#upload) | A thin, type-safe decorator over an adapter — the object you call `setFile()` and `to()` on. |
| [`LocalAdapter`](Local-Adapter.md) / [`FTPAdapter`](FTP-Adapter.md) / [`S3Adapter`](S3-Adapter.md) | Storage backends. Same API, different credentials. |
| [`UploadException`](Error-Handling.md) | Base exception for validation and storage failures. |

## Start here

- **New to the package?** Read [Installation](Installation.md), then
  [Quick Start](Quick-Start.md).
- **Want the mental model?** [Core Concepts](Core-Concepts.md) explains `File`,
  adapters, the `Upload` decorator and the `$target` argument.
- **Restricting what can be uploaded?** [Validation](Validation.md) covers
  extensions, MIME types and size limits.
- **Picking a backend?** [Local](Local-Adapter.md), [FTP](FTP-Adapter.md) and
  [S3](S3-Adapter.md) each have a dedicated page.
- **Looking for a specific method?** The full [API Reference](API-Reference.md)
  lists every public member.
- **Securing uploads?** [Security Best Practices](Security-Best-Practices.md) is
  required reading before going to production.

## What you get

- **One API, three backends.** Local disk, FTP/FTPS and Amazon S3 all
  implement the same [`UploadAdapterInterface`](API-Reference.md#uploadadapterinterface).
  Your code only changes which adapter it constructs.
- **Real validation.** Restrict by extension (case-insensitive), by the
  **real** MIME type detected with `finfo` (not the spoofable client header),
  and by size. See [Validation](Validation.md).
- **Correct file handling.** The extension comes from the original client
  name, not the temporary path; HTTP uploads are *moved*, path-loaded files
  are *copied*; destination directories are created automatically.
- **Multi-file ready.** [`File::setPost()`](The-File-Object.md#filesetpoststring-key-file)
  normalizes single- and multi-file form fields into one `File[]`.
- **One exception family.** Validation and backend failures throw
  [`UploadException`](Error-Handling.md); a single `catch` covers them all.

## At a glance — behaviour

| You write | What happens |
| --- | --- |
| `File::setPost('avatar')` | `$_FILES['avatar']` → `File[]` (single or multiple) |
| `$upload->setFile($file)->to()` | validate, then store at the destination root |
| `->to('avatars/2026')` | store under the `avatars/2026/` path/key prefix |
| `$file->rename('profile')` | store as `profile.<originalExtension>` |
| store succeeds | returns the `File`, with `getURL()` set |
| store write fails | returns `false` |
| validation fails | throws [`UploadException`](Error-Handling.md) |

## Package metadata

- **License**: MIT
- **Minimum PHP**: 8.2
- **Required extensions**: `ext-fileinfo`
- **Optional**: `ext-ftp` (FTP adapter), `aws/aws-sdk-php` (S3 adapter)
- **Packagist**: [`initphp/upload`](https://packagist.org/packages/initphp/upload)
- **Source**: [github.com/InitPHP/Upload](https://github.com/InitPHP/Upload)
- **Issues**: [github.com/InitPHP/Upload/issues](https://github.com/InitPHP/Upload/issues)
- **Discussions**: [github.com/orgs/InitPHP/discussions](https://github.com/orgs/InitPHP/discussions)

If something in this wiki is unclear, wrong, or out of date,
[open an issue](https://github.com/InitPHP/Upload/issues/new) — documentation
fixes are merged eagerly.
