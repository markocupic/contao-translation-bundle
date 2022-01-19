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

namespace Markocupic\ContaoTranslationBundle\TranslationTable;

use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Model\TransResourceModel;
use Markocupic\ContaoTranslationBundle\Model\TransTranslationModel;
use Markocupic\ContaoTranslationBundle\String\XmlSanitizer;

class TranslationTable
{
    private XmlSanitizer $xmlSanitizer;

    public function __construct(XmlSanitizer $xmlSanitizer)
    {
        $this->xmlSanitizer = $xmlSanitizer;
    }

    public function getRows(TransProjectModel $project, TransResourceModel $resource, string $language): array
    {
        $arrSources = [];
        $sources = TransTranslationModel::findByResourceAndLanguage($resource, $project->sourceLanguage);

        if (null !== $sources) {
            while ($sources->next()) {
                $arrSources[] = $sources->row();
            }
        }

        $arrTranslations = [];
        $translations = TransTranslationModel::findByResourceAndLanguage($resource, $language);

        if (null !== $translations) {
            while ($translations->next()) {
                $arrTranslations[$translations->translationId] = $translations->row();
            }
        }

        $rows = [];

        foreach ($arrSources as $source) {
            $strTranslation = '';

            if (isset($arrTranslations[$source['translationId']])) {
                $strTranslation = $arrTranslations[$source['translationId']]['translation'];
            }

            $rows[] = [
                'source' => [
                    'id' => $source['id'],
                    'resource' => $resource->id,
                    'language' => $project->sourceLanguage,
                    'translation_id' => $source['translationId'],
                    'translation' => $source['translation'],
                ],
                'target' => [
                    'resource' => $resource->id,
                    'language' => $language,
                    'translation_id' => $source['translationId'],
                    'translation' => $strTranslation,
                ],
            ];
        }

        return $rows;
    }

    /**
     * @throws \Exception
     */
    public function getTargetSourceValue(TransTranslationModel $source, string $language): string
    {
        $resource = $source->getRelated('pid');

        if (null === $resource) {
            return '';
        }

        $translation = TransTranslationModel::findOneBy(
            ['pid = ?', 'translationId = ?', 'language = ?'],
            [$resource->id, $source->translationId, $language]
        );

        if (null === $translation) {
            return '';
        }

        return $translation->translation;
    }

    /**
     * @throws \Exception
     */
    public function update(TransTranslationModel $source, string $language, string $value): bool
    {
        $resource = $source->getRelated('pid');

        if (null === $resource) {
            return false;
        }

        $translation = TransTranslationModel::findOneBy(
            ['pid = ?', 'translationId = ?', 'language = ?'],
            [$resource->id, $source->translationId, $language]
        );

        if (null === $translation) {
            $translation = new TransTranslationModel();
            $translation->pid = $resource->id;
            $translation->language = $language;
            $translation->translationId = $source->translationId;
            $translation->save();
        }

        // Trim, Encode "<" => "&lt;"
        $value = $this->xmlSanitizer->sanitize($value);

        $translation->translation = $value;
        $translation->tstamp = time();
        $translation->save();

        return true;
    }
}
