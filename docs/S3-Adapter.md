# S3 Adapter

`InitPHP\Upload\Adapters\S3Adapter` uploads files to an Amazon S3 (or
S3-compatible) bucket. It requires the AWS SDK; constructing it without
`aws/aws-sdk-php` throws an [`UnsupportedException`](Error-Handling.md#unsupportedexception).

```bash
composer require aws/aws-sdk-php
```

## Credentials

| Key | Type | Default | Description |
| --- | --- | --- | --- |
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

$adapter = new S3Adapter(
    [
        'key'        => getenv('AWS_ACCESS_KEY_ID'),
        'secret_key' => getenv('AWS_SECRET_ACCESS_KEY'),
        'region'     => 'eu-central-1',
        'bucket'     => 'my-app-uploads',
        'ACL'        => 'public-read',
    ],
    [
        'allowed_extensions' => ['jpg', 'png'],
        'allowed_mime_types' => ['image/jpeg', 'image/png'],
    ]
);

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

The S3 client is created lazily on the first upload, using the `key` /
`secret_key` / `region` / `version` from the credentials.

## How `$target` maps to S3

The **bucket always comes from the credentials.** `$target` is used as the
object **key prefix**, exactly like a sub-folder in the other adapters:

| Call | Object key | Bucket |
| --- | --- | --- |
| `to()` | `<name>` | `my-app-uploads` |
| `to('avatars')` | `avatars/<name>` | `my-app-uploads` |
| `to('avatars/2026')` | `avatars/2026/<name>` | `my-app-uploads` |

```php
$upload->setFile($file)->to('users/42/avatar');
// key: users/42/avatar/<name>   bucket: my-app-uploads
```

> **Compatibility note.** Earlier versions treated `$target` as the *bucket
> name*. It is now the key prefix in every adapter; the bucket is taken solely
> from the credentials. See the
> [changelog](https://github.com/InitPHP/Upload/blob/main/CHANGELOG.md).

## Object visibility (ACL)

The `ACL` credential is the canned ACL applied to each object. Common values:

| ACL | Effect |
| --- | --- |
| `public-read` | Anyone can read the object via its URL (default). |
| `private` | Only the bucket owner can read it. |
| `authenticated-read` | Any authenticated AWS user can read it. |

For private objects you typically generate pre-signed URLs with the AWS SDK
directly; this package returns the object's plain `ObjectURL`.

## S3-compatible services

Because the adapter wraps the official AWS SDK, it also works with
S3-compatible providers (MinIO, DigitalOcean Spaces, Cloudflare R2, …) **when
those are reachable with the standard SDK configuration** exposed here
(`key`, `secret_key`, `region`, `version`). Providers that require a custom
endpoint are best driven by constructing the `Aws\S3\S3Client` yourself; see
[Troubleshooting](Troubleshooting.md#s3-compatible-endpoints).

## Return value and errors

| Outcome | Result |
| --- | --- |
| Stored successfully | the [`File`](The-File-Object.md), with `getURL()` = the object's `ObjectURL` |
| The response carried no `ObjectURL` | `false` |
| Any SDK error (auth, network, permissions) | throws [`UploadException`](Error-Handling.md) (wrapping the SDK exception) |
| Validation failed | throws `UploadException` |
| The AWS SDK is not installed | constructor throws `UnsupportedException` |

```php
use InitPHP\Upload\Exceptions\UploadException;

try {
    $stored = $upload->setFile($file)->to('avatars');
} catch (UploadException $e) {
    // SDK error or validation failure
    $cause = $e->getPrevious(); // the underlying Aws exception, when wrapped
}
```

## Tips

- **Never hard-code AWS keys.** Read them from the environment. The adapter always
  passes `key` and `secret_key` to the SDK, so the SDK's default credential
  chain (IAM roles, `~/.aws/credentials`) is not used; for that, build the
  `Aws\S3\S3Client` yourself.
- The package builds the request with `SourceFile`, so the SDK streams the
  file from disk rather than buffering it in memory — fine for large objects.
- Pair `allowed_extensions` with `allowed_mime_types` so the stored key's
  extension matches the real content type.

## See also

- [Validation](Validation.md) · [Error Handling](Error-Handling.md)
- [Security Best Practices](Security-Best-Practices.md)
- [Local Adapter](Local-Adapter.md) · [FTP Adapter](FTP-Adapter.md)
