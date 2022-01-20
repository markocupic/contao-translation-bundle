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

use Contao\Environment;
use Markocupic\ContaoTranslationBundle\Model\TransLanguageModel;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Model\TransResourceModel;
use Markocupic\ContaoTranslationBundle\Model\TransTranslationModel;

/*
 * Backend modules
 */
$GLOBALS['BE_MOD']['translation']['trans_project'] = [
    'tables' => [
        'tl_trans_project',
        'tl_trans_language',
        'tl_trans_resource',
        'tl_trans_translation',
    ],
];

/*
 * Models
 */
$GLOBALS['TL_MODELS']['tl_trans_project'] = TransProjectModel::class;
$GLOBALS['TL_MODELS']['tl_trans_language'] = TransLanguageModel::class;
$GLOBALS['TL_MODELS']['tl_trans_resource'] = TransResourceModel::class;
$GLOBALS['TL_MODELS']['tl_trans_translation'] = TransTranslationModel::class;
