<?php

/**
 * UploadInvalidArgumentException.php
 *
 * This file is part of InitPHP Upload.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com>
 * @copyright  Copyright © 2023 InitPHP
 * @license    https://github.com/InitPHP/Upload/blob/main/LICENSE  MIT
 * @link       https://muhammetsafak.com
 */

declare(strict_types=1);

namespace InitPHP\Upload\Exceptions;

use InvalidArgumentException;

/**
 * Thrown for programmer errors in the arguments passed to the package, such as
 * loading a file from an empty path. Extends `\InvalidArgumentException` rather
 * than {@see UploadException}, signalling a misuse of the API rather than a
 * runtime upload failure.
 */
class UploadInvalidArgumentException extends InvalidArgumentException
{
}
