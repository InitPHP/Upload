<?php

/**
 * UnsupportedException.php
 *
 * This file is part of InitPHP Upload.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2023 InitPHP
 * @license    https://github.com/InitPHP/Upload/blob/main/LICENSE  MIT
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Upload\Exceptions;

/**
 * Thrown when an adapter cannot run in the current environment, e.g. a required
 * extension (`ext-ftp`) or dependency (`aws/aws-sdk-php`) is missing. Extends
 * {@see UploadException} so it is caught by the same handler.
 */
class UnsupportedException extends UploadException
{
}
