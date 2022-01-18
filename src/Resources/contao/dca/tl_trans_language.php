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

$GLOBALS['TL_DCA']['tl_trans_language'] = [
    // Config
    'config'   => [
        'dataContainer'    => 'Table',
        'ptable'           => 'tl_trans_project',
        'ctable'           => ['tl_trans_resource'],
        'enableVersioning' => true,
        'sql'              => [
            'keys' => [
                'id'  => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => 2,
            'fields'      => ['language'],
            'flag'        => 1,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label'             => [
            'fields' => ['language'],
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
            'edit'       => [
                'href' => 'table=tl_trans_resource',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'copy'       => [
                'label' => &$GLOBALS['TL_LANG']['tl_trans_language']['copy'],
                'href'  => 'act=copy',
                'icon'  => 'copy.svg',
            ],
            'delete'     => [
                'label'      => &$GLOBALS['TL_LANG']['tl_trans_language']['delete'],
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show'       => [
                'label'      => &$GLOBALS['TL_LANG']['tl_trans_language']['show'],
                'href'       => 'act=show',
                'icon'       => 'show.svg',
                'attributes' => 'style="margin-right:3px"',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{first_legend},language',
    ],
    'fields'   => [
        'id'       => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid'      => [
            'foreignKey' => 'tl_trans_project.name',
            'relation'   => [
                'type' => 'belongsTo',
                'load' => 'lazy',
            ],
            'sql'        => 'int(10) unsigned NOT NULL default 0',
        ],
        'tstamp'   => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'language' => [
            'inputType' => 'select',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'reference' => $GLOBALS['TL_LANG']['tl_trans_language'],
            'options'   => [
                'en',
                'fr',
                'de',
            ],
            'eval'      => [
                'includeBlankOption' => false,
                'tl_class'           => 'w50',
            ],
            'sql'       => "varchar(16) NOT NULL default ''",
        ],
    ],
];
