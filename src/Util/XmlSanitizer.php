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

class XmlSanitizer
{
    /**
     * Trim, replace &quot; with "
     * Encode not allowed & and <.
     */
    public function sanitize(string $strString): string
    {
        $strString = trim($strString);
        $strString = html_entity_decode($strString, ENT_QUOTES);

        return preg_replace('/&(amp;)?/i', '&amp;', $strString);
    }
}
