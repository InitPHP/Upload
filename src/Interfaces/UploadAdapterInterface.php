<?php
/**
 * UploadAdapterInterface.php
 *
 * This file is part of InitPHP.
 *
 * @author     Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright  Copyright © 2023 InitPHP
 * @license    http://initphp.github.io/license.txt  MIT
 * @version    1.0
 * @link       https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);

namespace InitPHP\Upload\Interfaces;

use InitPHP\Upload\File;

interface UploadAdapterInterface
{

    public function with(): self;

    public function setOption(string $name, $value): self;

    public function setOptions(array $options): self;

    public function withOption(string $name, $value): self;

    public function withOptions(array $options): self;


    public function setCredentials(array $credentials): self;

    public function withCredentials(array $credentials): self;

    public function setFile(File $file): self;

    /**
     * @param $target
     * @return File|false
     */
    public function to($target = null);

}
