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

namespace Markocupic\ContaoTranslationBundle\Import;

use Safe\Exceptions\FilesystemException;
use function Safe\file_get_contents;

class ParseXml
{
    private ?string $name = null;
    private ?string $dataType = null;
    private ?string $original = null;
    private ?string $sourceLanguage = null;
    private ?string $targetLanguage = null;
    private array $translations = [];
    private bool $isSourceFile = false;

    /**
     * @throws FilesystemException
     *
     * @return $this
     */
    public function parse(string $path): self
    {
        $this->reset();

        if (!is_file($path)) {
            throw new \Exception(sprintf('File "%s" not found.', $path));
        }
        $xml = simplexml_load_string(file_get_contents($path));

        $this->name = basename($path);
        $this->dataType = $this->getAttribute($xml->children(), 'datatype');
        $this->original = $this->getAttribute($xml->children(), 'original');
        $this->sourceLanguage = $this->getAttribute($xml->children(), 'source-language');
        $this->targetLanguage = $this->getAttribute($xml->children(), 'target-language');
        $this->translations = $this->getTranslationsAsArray($xml->children()->children());
        $this->isSourceFile = !$this->targetLanguage;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function getDataType(): ?string
    {
        return $this->dataType;
    }

    public function getOriginal(): ?string
    {
        return $this->original;
    }

    public function getSourceLanguage(): ?string
    {
        return $this->sourceLanguage;
    }

    public function getTargetLanguage(): ?string
    {
        return $this->targetLanguage;
    }

    public function getTranslations(): array
    {
        return $this->translations;
    }

    public function isSourceFile(): bool
    {
        return $this->isSourceFile;
    }

    private function reset(): void
    {
        $this->name = null;
        $this->dataType = null;
        $this->original = null;
        $this->sourceLanguage = null;
        $this->targetLanguage = null;
        $this->translations = [];
    }

    private function getAttribute(\SimpleXMLElement $node, string $attrName): ?string
    {
        foreach ($node->attributes() as $k => $v) {
            if ($k === $attrName) {
                return (string) $v;
            }
        }

        return null;
    }

    private function getTranslationsAsArray($node): array
    {
        $arrTrans = [];

        foreach ($node->children() as $v) {
            $arrTrans[] = [
                'id' => $this->getAttribute($v, 'id'),
                'source' => (string) $v->source[0],
                'target' => isset($v->target[0]) ? (string) $v->target[0] : null,
            ];
        }

        return $arrTrans;
    }
}
