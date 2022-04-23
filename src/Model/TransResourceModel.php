<?php

declare(strict_types=1);

/*
 * This file is part of Contao Translation Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-translation-bundle
 */

namespace Markocupic\ContaoTranslationBundle\Model;

use Contao\Database;
use Contao\Model;

class TransResourceModel extends Model
{
    protected static $strTable = 'tl_trans_resource';

    public static function findOneByProjectAndName(TransProjectModel $project, string $name): ?self
    {
        $objDb = Database::getInstance()
            ->prepare('SELECT * FROM tl_trans_resource WHERE name = ? AND pid = ?')
            ->limit(1)
            ->execute($name, $project->id)
        ;

        return !$objDb->numRows ? null : static::findByPk($objDb->id);
    }
}
