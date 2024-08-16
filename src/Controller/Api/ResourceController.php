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

namespace Markocupic\ContaoTranslationBundle\Controller\Api;

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Markocupic\ContaoTranslationBundle\Import\DbImport;
use Markocupic\ContaoTranslationBundle\Message\Message;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Model\TransResourceModel;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResourceController
{
    use AuthorizationTrait;

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $contaoFramework,
        private readonly DbImport $dbImport,
        private readonly Message $message,
        private readonly RequestStack $requestStack,
        private readonly TranslatorInterface $translator,
        private readonly string $projectDir,
    ) {
    }

    #[Route('/trans_api/resource/delete/{resourceId}', name: 'markocupic_contao_translation_api_delete_resource', defaults: ['_scope' => 'frontend', '_token_check' => true])]
    public function delete(int $resourceId): JsonResponse
    {
        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);

        if (null === ($project = TransResourceModel::findByPk($resourceId))) {
            $json = [
                'status' => 'error',
                'message' => 'Resource not found.',
            ];

            return new JsonResponse($json);
        }

        $this->connection->delete('tl_trans_translation', ['pid' => $resourceId]);
        $this->connection->delete('tl_trans_resource', ['id' => $resourceId]);

        $this->message->addConfirmation(
            $this->translator->trans('CT_TRANS.confirmDeleteResource', [$project->name], 'contao_default')
        );

        $json = [
            'status' => 'success',
        ];

        return new JsonResponse($json);
    }

    #[Route('/trans_api/resource/import_resources_from_path/{projectId}', name: 'markocupic_contao_translation_api_import_resources_from_path', defaults: ['_scope' => 'frontend', '_token_check' => true])]
    public function importLangFilesFromPath(int $projectId): JsonResponse
    {
        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);

        if (null === ($project = TransProjectModel::findByPk($projectId))) {
            $json = [
                'status' => 'error',
                'message' => 'Project not found.',
            ];

            return new JsonResponse($json);
        }
        $finder = new Finder();
        $finder->files()->in($this->projectDir.'/'.$project->languageFilesFolder)
            ->files()
            ->depth('< 5')->name('*.xlf')
        ;

        $arrFiles = [];

        foreach ($finder as $file) {
            $arrFiles[] = $file->getRealPath();
        }

        $this->dbImport->import($arrFiles, $project);

        $json = [
            'status' => 'success',
        ];

        return new JsonResponse($json);
    }
}
