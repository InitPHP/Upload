<?php

/**
 * UploadException.php
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

use RuntimeException;

/**
 * Thrown when a file cannot be validated or stored. This is the base type for
 * every runtime error raised by the package, so a single
 * `catch (UploadException $e)` covers them all.
 */
class UploadException extends RuntimeException {}
