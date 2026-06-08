# FTP adapter

`InitPHP\Upload\Adapters\FTPAdapter` uploads files to a remote server over FTP
or FTPS. It requires the `ftp` extension; constructing it without `ext-ftp`
throws an [`UnsupportedException`](exceptions.md).

## Credentials

| Key | Type | Default | Description |
| --- | ---- | ------- | ----------- |
| `host` | `string` | `''` | FTP server hostname. |
| `port` | `int` | `21` | FTP port. |
| `username` | `string` | `''` | Login user. |
| `password` | `string` | `''` | Login password. |
| `timeout` | `int` | `90` | Connection timeout in seconds. |
| `url` | `string` | `''` | Public base URL the remote root is served from. |
| `passive` | `bool` | `true` | Enable passive mode (recommended behind NAT/firewalls). |
| `ssl` | `bool` | `false` | Open an explicit FTPS (`ftp_ssl_connect`) connection. |

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\FTPAdapter;

$adapter = new FTPAdapter([
    'host'     => 'ftp.example.com',
    'username' => 'user',
    'password' => 'secret',
    'url'      => 'https://cdn.example.com/',
    'passive'  => true,
], [
    'allowed_max_size' => 10 * 1024 * 1024,
]);

$upload = new Upload($adapter);
```

## Storing files

```php
foreach (File::setPost('photos') as $file) {
    $stored = $upload->setFile($file)->to('gallery');

    if ($stored !== false) {
        echo $stored->getURL(); // https://cdn.example.com/gallery/<name>
    }
}
```

The connection is opened lazily on the first transfer and closed automatically
when the adapter is destroyed.

## Behaviour notes

- **Binary mode.** Transfers use `FTP_BINARY`, so images, archives and other
  non-text files are never corrupted by line-ending translation.
- **Passive mode.** Enabled by default. Set `'passive' => false` only if your
  server requires active mode.
- **FTPS.** Set `'ssl' => true` to connect with `ftp_ssl_connect()`.
- **Remote directories.** When `$target` names a sub-path
  (`to('gallery/2026')`), the directories are created on a best-effort basis
  before the file is sent.

## Return value and errors

- `to()` returns the stored `File` on success (with `getURL()` set), or `false`
  if the transfer returned false.
- Connection and authentication failures throw an
  [`UploadException`](exceptions.md):

| Situation | Message |
| --------- | ------- |
| Could not connect | `FTP connection failed.` |
| Bad credentials | `FTP username or password incorrect!` |

```php
use InitPHP\Upload\Exceptions\UploadException;

try {
    $upload->setFile($file)->to('gallery');
} catch (UploadException $e) {
    // connection, auth or validation problem
}
```
