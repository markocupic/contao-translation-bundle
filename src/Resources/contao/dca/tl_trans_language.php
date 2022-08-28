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

$GLOBALS['TL_DCA']['tl_trans_language'] = [
    'config'   => [
        'dataContainer'    => 'Table',
        'ptable'           => 'tl_trans_project',
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
            'edit'   => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy'   => [
                'href' => 'act=copy',
                'icon' => 'copy.svg',
            ],
            'delete' => [
                'href'       => 'act=delete',
                'icon'       => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show'   => [
                'label'      => &$GLOBALS['TL_LANG']['tl_trans_language']['show'],
                'href'       => 'act=show',
                'icon'       => 'show.svg',
                'attributes' => 'style="margin-right:3px"',
            ],
        ],
    ],
    'palettes' => [
        'default' => '{default_legend},language',
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
            'options'   => System::getContainer()->getParameter('markocupic_contao_translation.allowed_locales'),
            'eval'      => [
                'includeBlankOption' => false,
                'tl_class'           => 'w50',
            ],
            'sql'       => "varchar(16) NOT NULL default ''",
        ],
    ],
];
