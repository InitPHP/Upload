# InitPHP Upload

It is developed to upload files to local or remote server.

## Installation

```
composer require initphp/upload
```

## Usage

#### `\InitPHP\Upload\File::setPost()`

Returns a normalized `Array<\InitPHP\Upload\File>` from the array `$_FILES` with the specified `$key`.

#### `\InitPHP\Upload\File::setPath()`

Loads a specific file into the file object.

### Local Adapter

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;

$credentials = [
    'dir'   => __DIR__ . '/uploads/',
    'url'   => 'https://example.com/uploads/',
];

$options = [
    'allowed_extensions'    => [],
    'allowed_mime_types'    => [],
    'allowed_max_size'      => 0,
];

$adapter = new InitPHP\Upload\Adapters\LocalAdapter($credentials, $options);
$upload = new Upload($adapter);

foreach (File::setPost('files') as $file) {
    $upload->setFile($file)
            ->to();
}
```

### FTP Adapter

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;

$credentials = [
    'host'      => 'ftp.example.com',
    'port'      => 21,
    'username'  => 'user',
    'password'  => '123456',
    'timeout'   => 90,
    'url'       => 'http://example.com/',
];

$options = [
    'allowed_extensions'    => [],
    'allowed_mime_types'    => [],
    'allowed_max_size'      => 0,
];

$adapter = new InitPHP\Upload\Adapters\FTPAdapter($credentials, $options);
$upload = new Upload($adapter);

foreach (File::setPost('files') as $file) {
    $upload->setFile($file)
            ->to();
}
```

### AWS S3 Adapter

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;

$credentials = [
    'key'           => '',
    'secret_key'    => '',
    'region'        => '',
    'bucket'        => '',
    'ACL'           => 'public-read',
    'version'       => 'latest',
];

$options = [
    'allowed_extensions'    => [],
    'allowed_mime_types'    => [],
    'allowed_max_size'      => 0,
];

$adapter = new InitPHP\Upload\Adapters\S3Adapter($credentials, $options);
$upload = new Upload($adapter);

foreach (File::setPost('files') as $file) {
    $upload->setFile($file)
            ->to();
}
```

## Credits

- [Muhammet ŞAFAK](https://www.muhammetsafak.com.tr) <<info@muhammetsafak.com.tr>>

## License

Copyright &copy; 2023 [MIT License](./LICENSE)