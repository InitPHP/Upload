# Validation

Every adapter accepts the same validation **options** as its second
constructor argument. They are checked the moment you call `to()`. A violation
throws an [`UploadException`](Error-Handling.md) — it does **not** return `false`.

```php
$options = [
    'allowed_extensions' => ['jpg', 'jpeg', 'png'],
    'allowed_mime_types' => ['image/jpeg', 'image/png'],
    'allowed_max_size'   => 2 * 1024 * 1024, // 2 MB, in bytes
];

$adapter = new LocalAdapter($credentials, $options);
$upload  = new Upload($adapter);
```

An empty array or `0` means **"no restriction"** for that option. With no
options at all, any file that uploaded without error is accepted.

## The options

| Option | Type | Default | Meaning |
| --- | --- | --- | --- |
| `allowed_extensions` | `string[]` | `[]` | Allowed extensions, matched **case-insensitively** against the file's original name. |
| `allowed_mime_types` | `string[]` | `[]` | Allowed MIME types, matched against the **real** type detected with `finfo`. |
| `allowed_max_size` | `int` | `0` | Maximum size in **bytes**. |

## How a file is checked

`to()` runs the checks in this order and throws on the first failure:

1. **A file is set.** If you never called `setFile()`, it throws
   *"The file to be uploaded is undefined."*
2. **The upload succeeded.** If the file's [`getError()`](The-File-Object.md#geterror-int)
   is not `UPLOAD_ERR_OK`, it throws
   *"The file upload failed with error code N."*
3. **Extension** — if `allowed_extensions` is non-empty, the file's
   (lower-cased) extension must be in the list.
4. **MIME type** — if `allowed_mime_types` is non-empty, the file's
   [real MIME type](The-File-Object.md#getrealmimetype-string) must be in the
   list.
5. **Size** — if `allowed_max_size` is greater than `0`, the file's size must
   not exceed it.

## Setting options

You can pass options to the constructor, or set them later through the
[`Upload`](API-Reference.md#upload) decorator:

```php
$upload = new Upload(new LocalAdapter($credentials));

// One at a time…
$upload->setOption('allowed_max_size', 1024 * 1024);

// …or several at once.
$upload->setOptions([
    'allowed_extensions' => ['pdf'],
    'allowed_mime_types' => ['application/pdf'],
]);
```

Need a stricter variant for one request without mutating the shared instance?
Use the `with*` methods — they return a **new** `Upload`:

```php
$avatars = $upload->withOptions([
    'allowed_extensions' => ['jpg', 'png'],
    'allowed_max_size'   => 512 * 1024,
]);
// $upload is unchanged.
```

See [Core Concepts](Core-Concepts.md#3-upload--the-thing-you-call) for `set*` vs
`with*`.

## Case-insensitive extensions

Extension matching lower-cases both sides, so a single entry covers every
casing:

```php
$adapter = new LocalAdapter($credentials, [
    'allowed_extensions' => ['jpg'], // also accepts JPG, Jpg, jPg…
]);
```

## MIME type: the real one, not the claimed one

`allowed_mime_types` deliberately ignores the browser-supplied `type`, which is
trivial to forge. It uses `finfo` (the `fileinfo` extension) to read the file's
**actual** content type, so renaming `malware.php` to `cat.png` will not slip
past an `['image/png']` allow-list.

```php
// An attacker uploads a PHP script named "cat.png".
$adapter = new LocalAdapter($credentials, [
    'allowed_extensions' => ['png'],            // passes — the name ends in .png
    'allowed_mime_types' => ['image/png'],      // FAILS — finfo sees text/x-php
]);
```

> **Pair both checks.** `allowed_extensions` controls the stored file name;
> `allowed_mime_types` controls the actual content. Used together they are far
> stronger than either alone. See [Security Best Practices](Security-Best-Practices.md).

## Messages thrown

| Situation | Message |
| --- | --- |
| No file was set with `setFile()` | `The file to be uploaded is undefined.` |
| The upload errored (`UPLOAD_ERR_*`) | `The file upload failed with error code N.` |
| Extension not allowed | `This file extension is not allowed.` |
| MIME type not allowed | `This file type is not allowed.` |
| Larger than the limit | `Exceeds the maximum uploadable file size.` |

## Catching validation failures

```php
use InitPHP\Upload\Exceptions\UploadException;

try {
    $stored = $upload->setFile($file)->to();

    if ($stored === false) {
        $error = 'The file could not be written.';
    }
} catch (UploadException $e) {
    $error = $e->getMessage(); // e.g. "This file extension is not allowed."
}
```

## A note on `allowed_max_size`

The limit is in **bytes**. Build it from readable units:

```php
'allowed_max_size' => 5 * 1024 * 1024, // 5 MB
'allowed_max_size' => 512 * 1024,      // 512 KB
```

PHP's own `upload_max_filesize` and `post_max_size` directives apply *before*
your code runs. A file larger than those never reaches the library as a valid
upload — it arrives with an [upload error code](The-File-Object.md#geterror-int)
instead, which the package rejects in step 2 above. Keep `allowed_max_size`
at or below those `php.ini` limits.

## See also

- [The `File` Object](The-File-Object.md) — the values being validated
- [Security Best Practices](Security-Best-Practices.md) — defence in depth
- [Error Handling](Error-Handling.md) — the exception types
