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

use Markocupic\ContaoTranslationBundle\Controller\FrontendModule\TranslationModuleController;

/*
 * Backend modules
 */
$GLOBALS['TL_LANG']['MOD']['translation'] = 'Übersetzungen';
$GLOBALS['TL_LANG']['MOD']['trans_project'] = ['Projekte', 'Projekte'];

/*
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD']['translation'] = 'Übersetzungen';
$GLOBALS['TL_LANG']['FMD'][TranslationModuleController::TYPE] = ['Übersetzungsmodul', 'Übersetzungsmodul'];