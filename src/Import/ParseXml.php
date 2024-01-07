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

namespace Markocupic\ContaoTranslationBundle\Import;

use Safe\Exceptions\FilesystemException;
use function Safe\file_get_contents;

class ParseXml
{
    private string|null $name = null;
    private string|null $dataType = null;
    private string|null $original = null;
    private string|null $sourceLanguage = null;
    private string|null $targetLanguage = null;
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

    public function getName(): string|null
    {
        return $this->name;
    }

    public function getDataType(): string|null
    {
        return $this->dataType;
    }

    public function getOriginal(): string|null
    {
        return $this->original;
    }

    public function getSourceLanguage(): string|null
    {
        return $this->sourceLanguage;
    }

    public function getTargetLanguage(): string|null
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

    private function getAttribute(\SimpleXMLElement $node, string $attrName): string|null
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
