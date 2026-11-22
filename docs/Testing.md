# Testing

How to test code that uses `initphp/upload` without hitting a real network or
clobbering real files.

## Test the local adapter against a temp directory

The local adapter works entirely on the filesystem, so the easiest end-to-end
test points it at a throwaway directory and feeds it a file via
[`File::setPath()`](The-File-Object.md#filesetpathstring-path-file) (which
**copies**, leaving your fixture intact):

```php
use InitPHP\Upload\Upload;
use InitPHP\Upload\File;
use InitPHP\Upload\Adapters\LocalAdapter;
use PHPUnit\Framework\TestCase;

final class AvatarUploadTest extends TestCase
{
    public function testStoresAvatar(): void
    {
        $dir = sys_get_temp_dir() . '/upload-test-' . uniqid();
        mkdir($dir);

        $source = $dir . '/source.png';
        file_put_contents($source, 'fake-png-bytes');

        $upload = new Upload(new LocalAdapter([
            'dir' => $dir . '/store',
            'url' => 'https://cdn.test',
        ]));

        $stored = $upload->setFile(File::setPath($source))->to('avatars');

        self::assertNotFalse($stored);
        self::assertFileExists($dir . '/store/avatars/source.png');
        self::assertSame('https://cdn.test/avatars/source.png', $stored->getURL());
    }
}
```

## Simulate an HTML upload by setting `$_FILES`

`File::setPost()` reads the `$_FILES` superglobal, so you can drive it directly
in a test. Back it up and restore it so tests stay isolated:

```php
protected function setUp(): void
{
    $this->filesBackup = $_FILES;
    $_FILES = [];
}

protected function tearDown(): void
{
    $_FILES = $this->filesBackup;
}

public function testNormalizesMultiFileField(): void
{
    $_FILES['gallery'] = [
        'name'     => ['one.png', 'two.gif'],
        'type'     => ['image/png', 'image/gif'],
        'tmp_name' => ['/tmp/php1', '/tmp/php2'],
        'error'    => [UPLOAD_ERR_OK, UPLOAD_ERR_OK],
        'size'     => [100, 200],
    ];

    $files = File::setPost('gallery');

    self::assertCount(2, $files);
    self::assertSame('two.gif', $files[1]->getName());
}
```

> `move_uploaded_file()` only succeeds for genuine HTTP uploads, so a
> `$_FILES`-driven test with fake `tmp_name`s cannot exercise the local
> adapter's *move* branch. Use `File::setPath()` (the copy branch) for
> end-to-end storage assertions, as shown above.

## Fake the FTP/S3 backend with a test double

Both adapters expose protected **seams** you can override in a subclass, so you
can test your upload logic without a live server or the AWS SDK.

### FTP

`FTPAdapter` performs the transfer through two protected methods —
`prepareDirectory(string $remoteName)` and `upload(string $remoteName, string
$localPath): bool`. Override them to record calls:

```php
use InitPHP\Upload\Adapters\FTPAdapter;

final class FakeFTPAdapter extends FTPAdapter
{
    public array $uploaded = [];
    public bool $result = true;

    protected function prepareDirectory(string $remoteName): void
    {
        // no-op: no real connection
    }

    protected function upload(string $remoteName, string $localPath): bool
    {
        $this->uploaded[] = $remoteName;
        return $this->result;
    }
}

// In a test (skip when ext-ftp is absent):
$adapter = new FakeFTPAdapter(['url' => 'https://cdn.test']);
$stored  = (new Upload($adapter))->setFile($file)->to('gallery');

self::assertSame(['gallery/' . $file->getName()], $adapter->uploaded);
```

`FakeFTPAdapter`'s constructor still requires `ext-ftp`; guard the test with
`if (!extension_loaded('ftp')) self::markTestSkipped(...)`.

### S3

`S3Adapter` gets its client through a protected `getClient(): \Aws\S3\S3Client`.
Override it to return a recording fake, so you can assert the bucket, key and
ACL the adapter builds:

```php
use InitPHP\Upload\Adapters\S3Adapter;

final class FakeS3Adapter extends S3Adapter
{
    public array $lastArgs = [];

    protected function getClient(): \Aws\S3\S3Client
    {
        return new class($this) extends \Aws\S3\S3Client {
            public function __construct(private FakeS3Adapter $owner) {}
            public function putObject(array $args)
            {
                $this->owner->lastArgs = $args;
                return new \Aws\Result(['ObjectURL' => 'https://s3.test/' . $args['Key']]);
            }
        };
    }
}

$adapter = new FakeS3Adapter([
    'bucket' => 'my-bucket', 'region' => 'eu-central-1',
    'key' => 'k', 'secret_key' => 's',
]);
$stored = (new Upload($adapter))->setFile($file)->to('avatars');

self::assertSame('my-bucket', $adapter->lastArgs['Bucket']);
self::assertSame('avatars/' . $file->getName(), $adapter->lastArgs['Key']);
```

(The S3 adapter's constructor requires the `aws/aws-sdk-php` classes to exist.)

## How the package itself is tested

`initphp/upload` ships a full PHPUnit suite that uses exactly these techniques:
temp-directory tests for the local adapter, `$_FILES` manipulation for
`setPost()`, and seam-overriding test doubles for FTP and S3 (the AWS classes
are stubbed so the SDK is not required in CI). Run it with:

```bash
composer test     # PHPUnit
composer ci       # PHP-CS-Fixer + PHPStan (max) + PHPUnit
```

## See also

- [The `File` Object](The-File-Object.md) — `setPost()` / `setPath()`
- [Recipes](Recipes.md) — the code you would be testing
- [API Reference](API-Reference.md) — the seams (`BaseUploadAdapter`)
