# FAQ

### Does it support multiple files in one field?

Yes. [`File::setPost('field')`](The-File-Object.md#filesetpoststring-key-file)
returns a `File[]` for both `name="field"` and `name="field[]"`, so the same
loop handles one or many files.

### Where does the file extension come from?

From the **original client file name**, lower-cased. For a real upload the
temporary path (`tmp_name`) has no extension, so the name is the only reliable
source. See [The `File` Object](The-File-Object.md#getextension-string).

### Does `to()` overwrite an existing file?

Yes — there is no automatic collision check. If you store two files with the
same name in the same location, the second replaces the first. Generate unique
names with [`rename()`](The-File-Object.md#renamestring-rename-self) to avoid it;
see [Recipes](Recipes.md#generate-a-safe-unique-file-name).

### What is the difference between `getMimeType()` and `getRealMimeType()`?

`getMimeType()` returns the **client-declared** type (spoofable).
`getRealMimeType()` reads the file's actual bytes with `finfo`. Validation and
your security checks should use the real one. See
[The `File` Object](The-File-Object.md#declared-vs-real-mime-type).

### Is the original file deleted when I use `File::setPath()`?

No. Path-loaded files are **copied** by the local adapter, leaving your
original in place. Only genuine HTTP uploads are *moved*. See
[Core Concepts](Core-Concepts.md#move-vs-copy).

### Why did `to()` return `false` instead of throwing?

`false` means the underlying write itself failed (e.g. the destination was not
writable, or S3 returned no `ObjectURL`) — not a validation problem. Validation
and backend errors **throw** an [`UploadException`](Error-Handling.md). Check for
both. See [Error Handling](Error-Handling.md#to-returns-false-vs-throws).

### Does `$target` mean the bucket on S3?

No. `$target` is the object **key prefix** in every adapter; the S3 bucket
always comes from the credentials. (Older versions treated it as the bucket —
that changed.) See [S3 Adapter](S3-Adapter.md#how-target-maps-to-s3).

### Can I limit the upload size?

Yes, with the `allowed_max_size` option (in **bytes**). Keep it at or below
your `php.ini` `upload_max_filesize` / `post_max_size`. See
[Validation](Validation.md#a-note-on-allowed_max_size).

### Do I need the AWS SDK or the FTP extension if I only use local storage?

No. The core package depends only on `ext-fileinfo`. `ext-ftp` and
`aws/aws-sdk-php` are needed solely by their respective adapters. See
[Installation](Installation.md).

### Can I add my own storage backend?

Yes. Extend [`BaseUploadAdapter`](API-Reference.md#baseuploadadapter) and implement
`with()` and `to()`. You get credential/option handling, validation
(`checkFile()`) and prefix building (`targetName()`) for free.

### Does it work with S3-compatible providers (MinIO, R2, Spaces)?

It works with providers reachable through the standard SDK options exposed here
(`key`, `secret_key`, `region`, `version`). Providers needing a custom endpoint
are better served by building the `Aws\S3\S3Client` yourself. See
[Troubleshooting](Troubleshooting.md#s3-compatible-endpoints).

### Which PHP versions are supported?

PHP **8.2 and newer**. CI runs the test suite on 8.2–8.6.

### Is it framework-specific?

No. It is a standalone Composer package with no framework coupling. Use it from
plain PHP or any framework. See [Recipes](Recipes.md) for integration patterns.

## Still stuck?

- [Troubleshooting](Troubleshooting.md) — concrete fixes for common errors
- [Open an issue](https://github.com/InitPHP/Upload/issues/new)
- [Discussions](https://github.com/orgs/InitPHP/discussions)
