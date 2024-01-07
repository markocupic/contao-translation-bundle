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

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_trans_resource'] = [
    'config'   => [
        'dataContainer'    => DC_Table::class,
        'enableVersioning' => true,
        'ptable'           => 'tl_trans_project',
        'ctable'           => ['tl_trans_translation'],
        'sql'              => [
            'keys' => [
                'id'  => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'list'     => [
        'sorting'           => [
            'mode'        => DC_Table::MODE_SORTABLE,
            'fields'      => ['name'],
            'flag'        => DC_Table::MODE_SORTED,
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
            'edit'       => [
                'href' => 'table=tl_trans_translation',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'copy'       => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete'     => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show'       => [
                'href'       => 'act=show',
                'icon'       => 'show.svg',
                'attributes' => 'style="margin-right:3px"',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{default_legend},name,dataType,original',
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
        'name'     => [
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => DC_Table::MODE_SORTED,
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default ''",
        ],
        'original' => [
            'inputType' => 'text',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => DC_Table::MODE_SORTED,
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 512,
                'tl_class'  => 'clr',
            ],
            'sql'       => "varchar(512) NOT NULL default ''",
        ],
        'dataType' => [
            'inputType' => 'select',
            'exclude'   => true,
            'search'    => true,
            'filter'    => true,
            'sorting'   => true,
            'flag'      => DC_Table::MODE_SORTED,
            'options'   => ['php'],
            'eval'      => [
                'mandatory' => true,
                'maxlength' => 255,
                'tl_class'  => 'w50',
            ],
            'sql'       => "varchar(255) NOT NULL default 'php'",
        ],
    ],
];
