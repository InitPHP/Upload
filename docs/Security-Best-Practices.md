# Security Best Practices

File uploads are one of the most common attack surfaces in a web app. The
library gives you the tools; this page is the checklist for using them safely.

## 1. Validate the real content type, not the claimed one

The browser-supplied MIME type ([`getMimeType()`](The-File-Object.md#getmimetype-string))
and the file name are both attacker-controlled. Always restrict by the
**real** type, which the library detects with `finfo`:

```php
$options = [
    'allowed_extensions' => ['png', 'jpg', 'jpeg'],
    'allowed_mime_types' => ['image/png', 'image/jpeg'], // checked via finfo
    'allowed_max_size'   => 4 * 1024 * 1024,
];
```

With both set, `malware.php` renamed to `cat.png` is rejected: the extension
passes but `finfo` reports `text/x-php`, which is not in the allow-list. See
[Validation](Validation.md#mime-type-the-real-one-not-the-claimed-one).

## 2. Keep uploads out of the web root

If users can upload a file **and** the web server will execute files in that
directory, an uploaded `.php` becomes remote code execution. Store uploads in a
directory the web server does **not** execute:

```php
new LocalAdapter([
    'dir' => '/var/app/storage/uploads/',          // NOT under /public
    'url' => 'https://example.com/files/',          // served read-only
]);
```

Serve them through a controller, a read-only location, or a CDN — never from a
directory where the interpreter runs `.php`/`.phar` files.

## 3. Never trust the original file name — rename

User-controlled names invite path traversal, overwrites and odd characters.
Generate your own name; [`rename()`](The-File-Object.md#renamestring-rename-self)
keeps the correct extension:

```php
$file->rename(bin2hex(random_bytes(16))); // -> <32hex>.<ext>
$upload->setFile($file)->to('uploads');
```

The library normalizes the `$target` prefix (stripping and collapsing slashes),
but the safest path is to not let raw user input reach `$target` or the name at
all.

## 4. Block executable and double extensions

Use an **allow-list**, never a deny-list. Allow exactly the extensions you
expect:

```php
'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf'], // allow-list
```

A deny-list (`['php', 'phtml', ...]`) is always incomplete — new dangerous
extensions and server quirks (e.g. `file.php.jpg` on misconfigured servers)
keep finding ways through. The extension is taken from the original name, so an
allow-list of safe types is the reliable control.

## 5. Cap the size — in the app and in PHP

Set `allowed_max_size` (bytes), and keep it **at or below** your `php.ini`
limits:

```ini
; php.ini
upload_max_filesize = 8M
post_max_size       = 10M
```

```php
'allowed_max_size' => 8 * 1024 * 1024, // 8 MB
```

A file larger than the PHP limits never arrives as a valid upload; it comes in
with an [upload error code](The-File-Object.md#geterror-int), which the library
rejects automatically.

## 6. Prefer encrypted transports

- **FTP** sends credentials and data in clear text. Set `'ssl' => true` for
  FTPS, or use SFTP via a different tool. See [FTP Adapter](FTP-Adapter.md).
- **S3** is HTTPS by default through the SDK.

## 7. Keep secrets out of source control

Read FTP passwords and AWS keys from the environment, not from committed code:

```php
new S3Adapter([
    'key'        => getenv('AWS_ACCESS_KEY_ID'),
    'secret_key' => getenv('AWS_SECRET_ACCESS_KEY'),
    'region'     => getenv('AWS_REGION'),
    'bucket'     => getenv('AWS_BUCKET'),
]);
```

The S3 adapter always passes `key` and `secret_key` to the SDK and does not
fall back to the SDK's default credential chain. If you rely on IAM roles,
build the `Aws\S3\S3Client` yourself.

## 8. Mind object visibility on S3

The default ACL is `public-read`. For anything sensitive, use `'ACL' =>
'private'` and serve via pre-signed URLs generated with the AWS SDK directly.
See [S3 Adapter](S3-Adapter.md#object-visibility-acl).

## 9. Treat stored files as untrusted forever

Even after validation, do not assume a stored image is "safe":

- Serve user content from a separate domain/subdomain where practical.
- Set `Content-Disposition: attachment` and a correct `Content-Type` when
  serving downloads.
- Consider re-encoding images server-side to strip embedded payloads.

## Checklist

- [ ] `allowed_mime_types` set (real type via `finfo`)
- [ ] `allowed_extensions` is an allow-list of safe types
- [ ] `allowed_max_size` set, ≤ `php.ini` limits
- [ ] `dir` is outside the executable web root
- [ ] file names are generated, not taken from user input
- [ ] FTP uses `ssl => true`; secrets come from the environment
- [ ] S3 ACL matches the sensitivity of the data

## See also

- [Validation](Validation.md) · [The `File` Object](The-File-Object.md)
- [Local](Local-Adapter.md) · [FTP](FTP-Adapter.md) · [S3](S3-Adapter.md) adapters
