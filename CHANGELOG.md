# Changelog

All notable changes to `initphp/upload` are documented here. The format is
based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this
project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- The CI matrix now also covers PHP 8.6. The `php` constraint is unchanged
  (`"php": "^8.2"`).

## [3.0.1] - 2026-11-25

### Documentation

- The upload examples (README, Home, Quick Start, Local, FTP and S3 adapters,
  Recipes, Error Handling) are safe to copy: an allow-list of extensions and real
  MIME types, a `rename()` to a server-generated name, a `dir` outside the web
  root, and escaping of URLs and messages printed into HTML. The FTP examples read
  the password from the environment and pass the same options.
- The README states that with no options every extension and type is accepted.

## [3.0.0] - 2026-11-22

### Changed

- **BREAKING:** Raised the minimum PHP version to 8.2 (was 8.0;
  `"php": "^8.2"`). The CI matrix now covers PHP 8.2, 8.3, 8.4 and 8.5.
- **BREAKING:** The code style moved to PER-CS, enforced by the shared
  PHP-CS-Fixer configuration (`.php-cs-fixer.dist.php`). The source was
  reformatted accordingly; there are no behaviour changes.

## [2.0.0]

A correctness- and quality-focused overhaul: real bug fixes, consistent
`$target` semantics across adapters, a modern PHP 8 codebase, full tests,
static analysis, CI and documentation.

### Requirements

- **Raised the minimum PHP version to 8.0** (was 7.4).

### Fixed

- **File extension was read from the temporary upload path.** For a real HTTP
  upload the source is an extension-less `tmp_name`, so `getExtension()` always
  returned an empty string. This broke `allowed_extensions` validation and
  meant `rename()` could not re-attach an extension. The extension is now
  derived from the original client file name.
- **`TypeError` when file metadata could not be read.** Under
  `declare(strict_types=1)`, `filesize()`, `mime_content_type()` and
  `realpath()` returning `false` were assigned to `int`/`string` members and
  threw a raw `TypeError`. They now degrade gracefully (size `0`, a fallback
  MIME type, the raw path).
- **FTP corrupted binary files.** Transfers used `FTP_ASCII`, which mangles
  images, PDFs and archives via line-ending translation. They now use
  `FTP_BINARY`, and passive mode is enabled by default for NAT/firewall
  friendliness.
- **Local adapter could not store path-loaded files.** It used
  `move_uploaded_file()` for everything, which only works on genuine HTTP
  uploads, so files loaded with `File::setPath()` failed silently. Path-loaded
  files are now copied; real uploads are still moved.
- **Missing destination directories caused failures.** The local adapter now
  creates them recursively, and the FTP adapter creates remote directories on a
  best-effort basis.
- **Extension matching was case-sensitive.** `['jpg']` rejected `Photo.JPG`.
  Matching is now case-insensitive.

### Changed

- **S3 `$target` is now the object key prefix, not the bucket name.** Previously
  `to('avatars')` tried to upload to a bucket *named* `avatars`. `$target` now
  means a destination path/key prefix in every adapter; the bucket always comes
  from the credentials. **(Breaking.)**
- **`Upload` is a typed decorator.** It now implements `UploadAdapterInterface`
  and forwards each call explicitly; the `__call()` magic was removed. The
  `with*` methods return a new `Upload` (wrapping a fresh adapter clone) instead
  of a bare adapter. **(Breaking.)**
- **`File::setPath()` files are copied, not moved**, leaving the caller's
  original file in place. **(Breaking.)**

### Added

- `File::getPath()`, `File::getError()`, `File::isValid()` and
  `File::isUploaded()`.
- `$_FILES` normalization now skips `UPLOAD_ERR_NO_FILE` entries and preserves
  other upload error codes, which surface during validation.
- FTP `passive` and `ssl` (FTPS) credentials.
- A full PHPUnit test suite, PHPStan at max level, PHP-CS-Fixer (PSR-12), a
  GitHub Actions CI workflow, and developer documentation under `docs/`.
