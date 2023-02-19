<?php
/**
 * Upload.php
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

namespace InitPHP\Upload;

use InitPHP\Upload\Adapters\BaseUploadAdapter;

/**
 * @mixin BaseUploadAdapter
 */
class Upload
{

    protected BaseUploadAdapter $adapter;

    public function __construct(BaseUploadAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    public function __call($name, $arguments)
    {
        return $this->adapter->{$name}(...$arguments);
    }

}
