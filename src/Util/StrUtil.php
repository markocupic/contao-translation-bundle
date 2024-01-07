<?php

declare(strict_types=1);

/*
 * This file is part of Contao Translation Bundle.
 *
 * (c) Marko Cupic 2024 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-translation-bundle
 */

namespace Markocupic\ContaoTranslationBundle\Util;

class StrUtil
{
    public function sanitizeFolderDirectoryName(string $path): string
    {


      $path = stripslashes($path);
        $path = trim($path, "/");
        return trim($path, " \n\r\t\v\x00");
    }
}
