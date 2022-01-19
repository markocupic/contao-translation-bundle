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

use Contao\System;

/*
 * This file is part of Contao Translation Bundle.
 *
 * (c) Marko Cupic 2022 <m.cupic@gmx.ch>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-translation-bundle
 */

$GLOBALS['TL_DCA']['tl_trans_translation'] = [
    'config' => [
        'dataContainer' => 'Table',
        'enableVersioning' => true,
        'ptable' => 'tl_trans_resource',
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'fields' => ['sorting'],
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => [
                'translationId',
                'language',
            ],
            'format' => '%s [%s]',
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_trans_translation']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_trans_translation']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_trans_translation']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_trans_translation']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
                'attributes' => 'style="margin-right:3px"',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{first_legend},language,translationId,translation',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_trans_resource.name',
            'relation' => [
                'type' => 'belongsTo',
                'load' => 'lazy',
            ],
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'language' => [
            'inputType' => 'select',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'options' => System::getContainer()->getParameter('markocupic_contao_translation.allowed_locales'),
            'eval' => [
                'mandatory' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(16) NOT NULL default ''",
        ],
        'translationId' => [
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => 1,
            'eval' => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class' => 'clr',
            ],
            'sql' => "varchar(1024) NOT NULL default ''",
        ],
        'translation' => [
            'inputType' => 'text',
            'exclude' => true,
            'search' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => 1,
            'eval' => [
                'useRawRequestData' => true,
                'mandatory' => false,
                'tl_class' => 'clr',
            ],
            'sql' => 'text NULL',
        ],
        'sorting' => [
            'inputType' => 'text',
            'sql' => 'int(10) unsigned NOT NULL default 0',
        ],
    ],
];
