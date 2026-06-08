# S3 adapter

`InitPHP\Upload\Adapters\S3Adapter` uploads files to an Amazon S3 (or
S3-compatible) bucket. It requires the AWS SDK; constructing it without
`aws/aws-sdk-php` throws an [`UnsupportedException`](exceptions.md).

```bash
composer require aws/aws-sdk-php
```

## Credentials

| Key | Type | Default | Description |
| --- | ---- | ------- | ----------- |
| `key` | `string` | `''` | AWS access key ID. |
| `secret_key` | `string` | `''` | AWS secret access key. |
| `region` | `string` | `''` | Bucket region, e.g. `eu-central-1`. |
| `bucket` | `string` | `''` | Target bucket name. |
| `ACL` | `string` | `public-read` | Canned ACL applied to stored objects. |
| `version` | `string` | `latest` | S3 API version passed to the SDK. |

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\S3Adapter;

$adapter = new S3Adapter([
    'key'        => getenv('AWS_KEY'),
    'secret_key' => getenv('AWS_SECRET'),
    'region'     => 'eu-central-1',
    'bucket'     => 'my-app-uploads',
    'ACL'        => 'public-read',
], [
    'allowed_extensions' => ['jpg', 'png'],
]);

$upload = new Upload($adapter);
```

## Storing files

```php
foreach (File::setPost('photos') as $file) {
    $stored = $upload->setFile($file)->to('avatars');

    if ($stored !== false) {
        echo $stored->getURL(); // the object's public URL
    }
}
```

## How `$target` maps to S3

The **bucket always comes from the credentials**. `$target` is used as the
object **key prefix**, consistent with the other adapters:

| Call | Object key | Bucket |
| ---- | ---------- | ------ |
| `to()` | `<name>` | `my-app-uploads` |
| `to('avatars')` | `avatars/<name>` | `my-app-uploads` |
| `to('avatars/2026')` | `avatars/2026/<name>` | `my-app-uploads` |

> Earlier versions treated `$target` as the bucket name. It is now the key
> prefix everywhere — see the [changelog](../CHANGELOG.md).

## Return value and errors

- `to()` returns the stored `File` (with `getURL()` set to the object's
  `ObjectURL`) on success.
- It returns `false` if the SDK response carried no `ObjectURL`.
- Any SDK error (credentials, network, permissions) is wrapped in an
  [`UploadException`](exceptions.md).

```php
use InitPHP\Upload\Exceptions\UploadException;

try {
    $stored = $upload->setFile($file)->to('avatars');
} catch (UploadException $e) {
    // SDK error or validation failure
}
```
