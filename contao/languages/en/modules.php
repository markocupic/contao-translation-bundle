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

use Markocupic\ContaoTranslationBundle\Controller\FrontendModule\TranslationModuleController;

/*
 * Backend modules
 */
$GLOBALS['TL_LANG']['MOD']['translation'] = 'Translation APP';
$GLOBALS['TL_LANG']['MOD']['trans_projects'] = ['Projects', 'Edit projects'];

/*
 * Frontend modules
 */
$GLOBALS['TL_LANG']['FMD']['translation'] = 'Translation tools';
$GLOBALS['TL_LANG']['FMD'][TranslationModuleController::TYPE] = ['Translation APP', 'Add the translation app to the layout.'];
