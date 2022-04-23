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

namespace Markocupic\ContaoTranslationBundle\Import;

use Doctrine\DBAL\Connection;
use Markocupic\ContaoTranslationBundle\Message\Message;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Model\TransResourceModel;
use Markocupic\ContaoTranslationBundle\String\XmlSanitizer;
use Symfony\Contracts\Translation\TranslatorInterface;

class DbImport
{
    private ParseXml $parseXml;
    private Connection $connection;
    private XmlSanitizer $xmlSanitizer;
    private Message $message;
    private TranslatorInterface $translator;
    private array $sourceLangFiles = [];
    private array $targetLangFiles = [];

    public function __construct(ParseXml $parseXml, Connection $connection, XmlSanitizer $xmlSanitizer, Message $message, TranslatorInterface $translator)
    {
        $this->parseXml = $parseXml;
        $this->connection = $connection;
        $this->xmlSanitizer = $xmlSanitizer;
        $this->message = $message;
        $this->translator = $translator;
    }

    public function import(array $arrSourcePaths, TransProjectModel $project): void
    {
        foreach ($arrSourcePaths as $sourcePath) {
            // Clone!!!
            $xliff = clone $this->parseXml->parse($sourcePath);

            if ($xliff->isSourceFile()) {
                $this->sourceLangFiles[] = $xliff;
            } else {
                $this->targetLangFiles[] = $xliff;
            }
        }

        // Run through source files first!!!
        $arrSources = array_merge($this->sourceLangFiles, $this->targetLangFiles);

        $this->connection->beginTransaction();

        try {
            foreach ($arrSources as $xliff) {
                if (null !== $xliff && $xliff->getName() && $xliff->getSourceLanguage()) {
                    // Add language if not exists
                    $lang = $xliff->getTargetLanguage() ?: $xliff->getSourceLanguage();

                    if (!$this->connection->fetchOne('SELECT id FROM tl_trans_language WHERE pid = ? AND language = ?', [$project->id, $lang])) {
                        $set = [
                            'tstamp' => time(),
                            'pid' => $project->id,
                            'language' => $lang,
                        ];

                        $this->connection->insert('tl_trans_language', $set);
                    }

                    $source = TransResourceModel::findOneByProjectAndName($project, $xliff->getName());

                    if (null === $source) {
                        $source = new TransResourceModel();
                        $source->pid = $project->id;
                        $source->name = $xliff->getName();
                        $source->original = $xliff->getOriginal();
                        $source->save();
                    }

                    $source->dataType = $xliff->getDataType();
                    $source->tstamp = time();
                    $source->save();

                    $this->message->addConfirmation(
                        $this->translator->trans('CT_TRANS.confirmResourceImport', [$source->name], 'contao_default')
                    );

                    $this->connection->delete(
                        'tl_trans_translation',
                        [
                            'pid' => $source->id,
                            'language' => $xliff->getTargetLanguage() ?: $xliff->getSourceLanguage(),
                        ],
                    );

                    $sorting = 0;

                    $arrTranslations = $xliff->getTranslations();

                    foreach ($arrTranslations as $arrTrans) {
                        $strTranslation = $xliff->isSourceFile() ? (string) $arrTrans['source'] : (string) $arrTrans['target'];

                        // Sanitize string
                        $strTranslation = $this->xmlSanitizer->sanitize($strTranslation);
                        $set = [
                            'pid' => $source->id,
                            'tstamp' => time(),
                            'translationId' => $arrTrans['id'],
                            'translation' => $strTranslation,
                            'language' => $xliff->getTargetLanguage() ?: $xliff->getSourceLanguage(),
                            'sorting' => $sorting += 10,
                        ];

                        if ('' !== $set['translation']) {
                            $this->connection->insert('tl_trans_translation', $set);
                        }
                    }

                    $this->deleteWithoutSource($source);
                } else {
                    $this->message->addError(
                        $this->translator->trans('CT_TRANS.importResourceError', [], 'contao_default')
                    );
                }
            }

            $this->connection->commit();
        } catch (\Exception $e) {
            $this->connection->rollBack();

            throw $e;
        }
    }

    private function deleteWithoutSource(TransResourceModel $source): void
    {
        $project = $source->getRelated('pid');

        if (null === $project) {
            throw new Exception(sprintf('Resource with ID %s has no corresponding parent project.', $source->id, ));
        }

        $stmt = $this->connection->executeQuery(
            'SELECT * FROM tl_trans_translation WHERE pid = ? AND language != ?',
            [$source->id, $project->sourceLanguage]
        );

        while (false !== ($row = $stmt->fetchAssociative())) {
            if (
                !$this->connection->fetchOne(
                    'SELECT id FROM tl_trans_translation WHERE pid = ? AND translationId = ? AND language = ?',
                    [$source->id, $row['translationId'], $project->sourceLanguage],
                )
            ) {
                $this->connection->delete(
                    'tl_trans_translation',
                    ['id' => $row['id']]
                );

                $this->message->addInfo(
                    $this->translator->trans('CT_TRANS.deleteOrphaned', [$row['translationId']], 'contao_default')
                );
            }
        }
    }
}
