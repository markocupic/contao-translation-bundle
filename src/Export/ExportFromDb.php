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

namespace Markocupic\ContaoTranslationBundle\Export;

use Codefog\HasteBundle\UrlParser;
use Contao\Controller;
use Contao\CoreBundle\Exception\ResponseException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\Exception;
use Markocupic\ContaoTranslationBundle\Message\Message;
use Markocupic\ContaoTranslationBundle\Model\TransLanguageModel;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Model\TransResourceModel;
use Markocupic\ZipBundle\Zip\Zip;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Component\String\UnicodeString;
use Symfony\Contracts\Translation\TranslatorInterface;

class ExportFromDb
{
    private array $resources = [];
    private array $languages = [];

    public function __construct(
        private readonly Connection $connection,
        private readonly Message $message,
        private readonly TranslatorInterface $translator,
        private readonly UrlParser $urlParser,
        private readonly string $projectDir,
    ) {
    }

    /**
     * @throws \DOMException
     * @throws Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function export(TransProjectModel $project, bool $repoImport = false, TransResourceModel $resource = null, array $arrLanguage = null): void
    {
        $error = 0;
        $countFilesCreated = 0;

        // Get resource(s)
        if (null !== $resource) {
            $this->resources[] = $resource;
        } else {
            if (null !== ($resource = TransResourceModel::findByPid($project->id))) {
                while ($resource->next()) {
                    $this->resources[] = $resource->current();
                }
            }
        }

        // Get language(s)
        if (!empty($arrLanguage)) {
            $this->languages = $arrLanguage;
        } else {
            if (null !== ($language = TransLanguageModel::findByPid($project->id))) {
                while ($language->next()) {
                    $this->languages[] = $language->language;
                }
            }
        }

        $tempFolder = $this->projectDir.'/system/tmp/'.Uuid::uuid4()->toString();
        mkdir($tempFolder);

        foreach ($this->languages as $language) {
            // Create a temp folder for each language
            $targetPath = $tempFolder.'/'.$language;
            mkdir($targetPath);

            foreach ($this->resources as $resource) {
                // Source language array
                $arrSourceTranslations = [];
                $stmt = $this->connection->executeQuery('SELECT * FROM tl_trans_translation WHERE language = ? AND pid = ? ORDER BY sorting', [$project->sourceLanguage, $resource->id]);

                while (false !== ($row = $stmt->fetchAssociative())) {
                    $arrSourceTranslations[$row['translationId']] = $row['translation'];
                }

                // Target language array
                $arrTargetTranslations = [];
                $stmt = $this->connection->executeQuery('SELECT * FROM tl_trans_translation WHERE language = ? AND pid = ?', [$language, $resource->id]);

                while (false !== ($row = $stmt->fetchAssociative())) {
                    $arrTargetTranslations[$row['translationId']] = $row['translation'];
                }

                if (empty($arrTargetTranslations)) {
                    $this->message->addError(
                        $this->translator->trans('CT_TRANS.errorRepositoryExportDueToEmptyFile', [$language, $resource->name], 'contao_default')
                    );
                    continue;
                }

                $writer = new XliffWriter(
                    $project->sourceLanguage,
                    $language,
                    $resource->original,
                    $targetPath.'/'.$resource->name,
                    $arrSourceTranslations,
                    $arrTargetTranslations
                );

                if (!$writer->export()) {
                    ++$error;

                    $this->message->addError(
                        $this->translator->trans('CT_TRANS.errorRepositoryExport', [$language, $resource->name], 'contao_default')
                    );
                } else {
                    ++$countFilesCreated;

                    $this->message->addConfirmation(
                        $this->translator->trans('CT_TRANS.confirmRepositoryExport', [$language, $resource->name], 'contao_default')
                    );
                }
            }
        }

        if ($repoImport) {
            $fs = new Filesystem();
            $fs->mirror($tempFolder, $this->projectDir.'/'.$project->languageFilesFolder);
            $url = $this->urlParser->removeQueryString(['do', 'repo_import']);
            Controller::redirect($url);
        }

        if (!$error && $countFilesCreated) {
            (new Zip())
                ->ignoreDotFiles(false)
                ->stripSourcePath($tempFolder)
                ->addDirRecursive($tempFolder)
                ->run($tempFolder.'/'.$project->name.'.zip')
            ;

            $this->sendFileToBrowser($tempFolder.'/'.$project->name.'.zip', $project->name.'.zip');
        }
    }

    private function sendFileToBrowser(string $filePath, string $filename = '', bool $inline = false): void
    {
        $response = new BinaryFileResponse($filePath);
        $response->setPrivate(); // public by default
        $response->setAutoEtag();

        $response->setContentDisposition(
            $inline ? ResponseHeaderBag::DISPOSITION_INLINE : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $filename,
            (new UnicodeString(basename($filePath)))->ascii()->toString()
        );
        $mimeTypes = new MimeTypes();
        $mimeType = $mimeTypes->guessMimeType($filePath);

        $response->headers->addCacheControlDirective('must-revalidate');
        $response->headers->set('Connection', 'close');
        $response->headers->set('Content-Type', $mimeType);

        throw new ResponseException($response);
    }
}
