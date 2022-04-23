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

namespace Markocupic\ContaoTranslationBundle\Export;

class XliffWriter
{
    protected const XLIFF_VERSION = '1.1';
    protected const FILE_DATATYPE = 'php';

    protected string $sourceLanguage;
    protected string $targetLanguage;
    protected string $originalFilePath;
    protected string $targetFilePath;
    protected array $arrTranslations;
    private array $arrSourceLangTranslations;
    private array $arrTargetLangTranslations;

    public function __construct(string $sourceLanguage, string $targetLanguage, string $originalFilePath, string $targetFilePath, array $arrSourceLangTranslations, array $arrTargetLangTranslations)
    {
        $this->sourceLanguage = $sourceLanguage;
        $this->targetLanguage = $targetLanguage;
        $this->originalFilePath = $originalFilePath;
        $this->targetFilePath = $targetFilePath;
        $this->arrSourceLangTranslations = $arrSourceLangTranslations;
        $this->arrTargetLangTranslations = $arrTargetLangTranslations;
    }

    /**
     * @throws \DOMException
     */
    public function export(): bool
    {
        // First create the Xml Document
        $dom = $this->createXmlDocument();

        // Create root node
        $bodyNode = $this->addRootNodes($dom);

        $intAppendedItems = 0;

        // Add translation items
        foreach (array_keys($this->arrSourceLangTranslations) as $translationId) {
            // Do not add empty or unsetted values
            if (!isset($this->arrSourceLangTranslations[$translationId])) {
                continue;
            }

            if ('' === trim((string) $this->arrSourceLangTranslations[$translationId])) {
                continue;
            }

            if (!isset($this->arrTargetLangTranslations[$translationId])) {
                continue;
            }

            if ('' === trim((string) $this->arrTargetLangTranslations[$translationId])) {
                continue;
            }

            // Get the source translation slug
            $valueSource = $this->arrSourceLangTranslations[$translationId];

            // Get the target translation slug
            $valueTarget = $this->arrTargetLangTranslations[$translationId];

            // Append item
            $bodyNode->appendChild($this->createTranslationNode($dom, $translationId, $valueSource, $valueTarget));
            ++$intAppendedItems;
        }

        $bytes = false;

        if ($intAppendedItems) {
            $bytes = file_put_contents($this->targetFilePath, $dom->saveXML());
        }

        return (bool) $bytes;
    }

    /**
     * Create a new xml document.
     */
    protected function createXmlDocument(): \DOMDocument
    {
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->formatOutput = true;

        return $dom;
    }

    /**
     * Add root nodes to a document.
     *
     * @throws \DOMException
     */
    protected function addRootNodes(\DOMDocument $dom): \DOMNode
    {
        $xliff = $dom->appendChild($dom->createElement('xliff'));
        $xliff->appendChild(new \DOMAttr('version', self::XLIFF_VERSION));

        $fileNode = $xliff->appendChild($dom->createElement('file'));
        $fileNode->appendChild(new \DOMAttr('datatype', self::FILE_DATATYPE));
        $fileNode->appendChild(new \DOMAttr('original', $this->originalFilePath));
        $fileNode->appendChild(new \DOMAttr('source-language', $this->sourceLanguage));

        if ($this->sourceLanguage !== $this->targetLanguage) {
            $fileNode->appendChild(new \DOMAttr('target-language', $this->targetLanguage));
        }

        return $fileNode->appendChild($dom->createElement('body'));
    }

    /**
     * Create a new trans-unit node.
     *
     * @throws \DOMException
     *
     * @return \DOMElement|false
     */
    protected function createTranslationNode(\DOMDocument $dom, string $translationId, string $valueSource, ?string $valueTarget)
    {
        $translationNode = $dom->createElement('trans-unit');
        $translationNode->appendChild(new \DOMAttr('id', $translationId));

        // Add source
        $source = $dom->createElement('source');

        if ($this->hasNotAllowedChars($valueSource)) {
            $elementCdata = $dom->createCDATASection($valueSource);
            $source->appendChild($elementCdata);
        } else {
            $source->textContent = $valueSource;
        }

        $translationNode->appendChild($source);

        // Add target
        if ($this->sourceLanguage !== $this->targetLanguage) {
            if ($valueTarget && '' !== $valueTarget) {
                $target = $dom->createElement('target');

                if ($this->hasNotAllowedChars($valueTarget)) {
                    $elementCdata = $dom->createCDATASection($valueTarget);
                    $target->appendChild($elementCdata);
                } else {
                    $target->textContent = $valueTarget;
                }

                $translationNode->appendChild($target);
            }
        }

        return $translationNode;
    }

    protected function hasNotAllowedChars(string $strString): bool
    {
        $hasNotAllowedChars = false;

        if (false !== strpos($strString, '<') || false !== strpos($strString, '>')) {
            $hasNotAllowedChars = true;
        }

        return $hasNotAllowedChars;
    }
}
