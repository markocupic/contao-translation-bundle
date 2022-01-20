<?php

declare(strict_types=1);

/*
 * This file is part of Contao Translation Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-translation-bundle
 */

namespace Markocupic\ContaoTranslationBundle\Model;

use Contao\Database;
use Contao\Model;
use Contao\Model\Collection;

class TransTranslationModel extends Model
{
    protected static $strTable = 'tl_trans_translation';

    public static function countUntranslatedByProjectAndLanguage(TransProjectModel $project, string $language): int
    {
        $total = static::countTranslatedByProjectAndLanguage($project, $project->sourceLanguage);
        $translated = static::countTranslatedByProjectAndLanguage($project, $language);

        return $total - $translated;
    }

    public static function countTranslatedByProjectAndLanguage(TransProjectModel $project, string $language): int
    {
        $objDb = Database::getInstance()
            ->prepare('SELECT COUNT(id) as total FROM tl_trans_translation WHERE translation != ? AND language = ? AND pid IN (SELECT id FROM tl_trans_resource WHERE pid = ?)')
            ->execute('', $language, $project->id)
        ;

        return (int) $objDb->total;
    }

    /**
     * @throws \Exception
     */
    public static function countUntranslatedByResourceAndLanguage(TransResourceModel $resource, string $language): int
    {
        $total = static::countTranslatedByResourceAndLanguage($resource, $resource->getRelated('pid')->sourceLanguage);
        $translated = static::countTranslatedByResourceAndLanguage($resource, $language);

        return $total - $translated;
    }

    public static function countTranslatedByResourceAndLanguage(TransResourceModel $resource, string $language): int
    {
        $objDb = Database::getInstance()
            ->prepare('SELECT COUNT(id) as total FROM tl_trans_translation WHERE translation != ? AND language = ? AND pid = ?')
            ->execute('', $language, $resource->id)
        ;

        return (int) $objDb->total;
    }

    public static function findByResourceAndLanguage(TransResourceModel $resource, string $language): ?Collection
    {
        return self::findBy(
            ['tl_trans_translation.translation != ?', 'tl_trans_translation.language = ?', 'tl_trans_translation.pid = ?'],
            ['', $language, $resource->id],
            [
                'order' => 'tl_trans_translation.sorting',
            ]
        );
    }
}
