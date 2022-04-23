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

$GLOBALS['TL_DCA']['tl_trans_project'] = [
    'config'   => [
        'dataContainer'    => 'Table',
        'enableVersioning' => true,
        'ctable'           => [
            'tl_trans_language',
            'tl_trans_resource',
        ],
        'sql'              => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => 2,
            'fields'      => ['name'],
            'flag'        => 1,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields' => ['name'],
            'format' => '%s',
        ],
        'global_operations' => [
            'all' => [
                'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href'       => 'act=select',
                'class'      => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations'        => [
            'editheader' => [
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'editLang'   => [
                'href' => 'table=tl_trans_language',
                'icon' => 'edit.svg',
            ],
            'editRes'    => [
                'href' => 'table=tl_trans_resource',
                'icon' => 'iconPLAIN.svg',
            ],
            'copy'       => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete'     => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show'       => [
                'href'       => 'act=show',
                'icon'       => 'show.svg',
                'attributes' => 'style="margin-right:3px"',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{default_legend},name,sourceLanguage,languageFilesFolder',
    ],
    'fields'   => [
        'id'                  => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp'              => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'name'                => [
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => 1,
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'sourceLanguage'      => [
            'inputType' => 'select',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'options'   => System::getContainer()->getParameter('markocupic_contao_translation.allowed_locales'),
            'eval'      => [
                'mandatory'          => true,
                'placeholder'        => 'vendor-name/project-name',
                'includeBlankOption' => false,
                'tl_class'           => 'w50',
            ],
            'sql'       => "varchar(16) NOT NULL default 'en'",
        ],
        'languageFilesFolder' => [
            'inputType' => 'text',
            'exclude'   => true,
            'eval'      => [
                'mandatory'     => true,
                'maxlength'     => 512,
                'placeholder'   => 'vendor-name/project-name/src/Resources/contao/languages',
                'tl_class'      => 'clr',
                'trailingSlash' => false,
            ],
            'sql'       => "varchar(512) NOT NULL default 'vendor-name/project-name/src/Resources/contao/languages'",
        ],
    ],
];
