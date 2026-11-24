# FTP Adapter

`InitPHP\Upload\Adapters\FTPAdapter` uploads files to a remote server over FTP
or FTPS. It requires the **`ftp`** extension; constructing it without `ext-ftp`
throws an [`UnsupportedException`](Error-Handling.md#unsupportedexception).

## Credentials

| Key | Type | Default | Description |
| --- | --- | --- | --- |
| `host` | `string` | `''` | FTP server hostname. |
| `port` | `int` | `21` | FTP port. |
| `username` | `string` | `''` | Login user. |
| `password` | `string` | `''` | Login password. |
| `timeout` | `int` | `90` | Connection timeout in seconds. |
| `url` | `string` | `''` | Public base URL the remote root is served from. |
| `passive` | `bool` | `true` | Use passive mode (recommended behind NAT/firewalls). |
| `ssl` | `bool` | `false` | Open an explicit FTPS (`ftp_ssl_connect`) connection. |

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\FTPAdapter;

$adapter = new FTPAdapter(
    [
        'host'     => 'ftp.example.com',
        'username' => 'deploy',
        'password' => getenv('FTP_PASSWORD'),
        'url'      => 'https://cdn.example.com/',
        'passive'  => true,
    ],
    [
        // With no allow-list, every extension is accepted and the file lands in
        // the publicly served `url` tree. Always restrict what can be stored.
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
        'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'], // real type, via finfo
        'allowed_max_size'   => 10 * 1024 * 1024,
    ]
);

$upload = new Upload($adapter);
```

## Storing files

```php
foreach (File::setPost('photos') as $file) {
    $file->rename(bin2hex(random_bytes(16))); // never use the client's file name

    $stored = $upload->setFile($file)->to('gallery');

    if ($stored !== false) {
        echo htmlspecialchars((string) $stored->getURL(), ENT_QUOTES, 'UTF-8'); // https://cdn.example.com/gallery/<32hex>.jpg
    }
}
```

The connection is opened **lazily** on the first transfer and closed
automatically when the adapter is destroyed.

## Behaviour notes

- **Binary mode.** Transfers use `FTP_BINARY`, so images, archives, PDFs and
  every other non-text file are transferred byte-for-byte — never corrupted by
  line-ending translation.
- **Passive mode.** Enabled by default. Most clients behind NAT or a firewall
  need it. Set `'passive' => false` only if your server explicitly requires
  active mode.
- **FTPS.** Set `'ssl' => true` to connect with `ftp_ssl_connect()` (explicit
  FTP-over-TLS). Plain FTP sends credentials and data in the clear — prefer
  FTPS, or SFTP via another tool, whenever possible.
- **Remote directories.** When `$target` names a sub-path
  (`to('gallery/2026')`), the directories are created on a best-effort basis,
  segment by segment, before the file is sent.

## FTPS example

```php
$adapter = new FTPAdapter([
    'host'     => 'ftp.example.com',
    'port'     => 21,
    'username' => 'deploy',
    'password' => getenv('FTP_PASSWORD'),
    'url'      => 'https://cdn.example.com/',
    'ssl'      => true,   // explicit FTPS
    'passive'  => true,
], [
    // FTPS protects the transfer, not the content: without an allow-list every
    // extension is accepted. Use the same options as in the example above.
    'allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    'allowed_max_size'   => 10 * 1024 * 1024,
]);
```

## Return value and errors

| Outcome | Result |
| --- | --- |
| Stored successfully | the [`File`](The-File-Object.md), with `getURL()` set |
| The transfer returned false | `false` |
| Could not connect | throws [`UploadException`](Error-Handling.md): *FTP connection failed.* |
| Bad credentials | throws `UploadException`: *FTP username or password incorrect!* |
| Validation failed | throws `UploadException` |

```php
use InitPHP\Upload\Exceptions\UploadException;
use InitPHP\Upload\Exceptions\UnsupportedException;

try {
    $upload = new Upload(new FTPAdapter($credentials, $options));
    $stored = $upload->setFile($file)->to('gallery');
} catch (UnsupportedException $e) {
    // ext-ftp is not installed
} catch (UploadException $e) {
    // connection, authentication or validation problem
}
```

(`UnsupportedException` extends `UploadException`, so a single
`catch (UploadException $e)` also catches the missing-extension case.)

## Tips

- **Store secrets in the environment**, not in source — see the example's
  `getenv('FTP_PASSWORD')`.
- **`url`** is only used to build the public URL string; it usually points at
  the HTTP(S) host that serves the FTP root, which may differ from `host`.
- A `timeout` that is too low can make large uploads fail mid-transfer; raise
  it for big files or slow links.

## See also

- [Validation](Validation.md) · [Error Handling](Error-Handling.md)
- [Local Adapter](Local-Adapter.md) · [S3 Adapter](S3-Adapter.md)
- [Troubleshooting](Troubleshooting.md#ftp-uploads-fail-or-hang)
