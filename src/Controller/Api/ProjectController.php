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

namespace Markocupic\ContaoTranslationBundle\Controller\Api;

use Contao\CoreBundle\Framework\ContaoFramework;
use Doctrine\DBAL\Connection;
use Markocupic\ContaoTranslationBundle\Export\ExportFromDb;
use Markocupic\ContaoTranslationBundle\Message\Message;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectController
{
    use AuthorizationTrait;

    private ContaoFramework $contaoFramework;
    private RequestStack $requestStack;
    private Connection $connection;
    private ExportFromDb $exportFromDb;
    private Message $message;
    private TranslatorInterface $translator;

    public function __construct(ContaoFramework $contaoFramework, RequestStack $requestStack, Connection $connection, ExportFromDb $exportFromDb, Message $message, TranslatorInterface $translator)
    {
        $this->contaoFramework = $contaoFramework;
        $this->requestStack = $requestStack;
        $this->connection = $connection;
        $this->exportFromDb = $exportFromDb;
        $this->message = $message;
        $this->translator = $translator;
    }

    /**
     * @Route("/trans_api/project/delete/{projectId}",
     *     name="markocupic_contao_translation_api_delete_project",
     *     defaults={
     *         "_scope" = "frontend",
     *         "_token_check" = true
     *     }
     * )
     *
     * @throws \Exception
     */
    public function delete(int $projectId): JsonResponse
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

        // Delete translations
        $this->connection->executeQuery(
            'DELETE FROM tl_trans_translation WHERE tl_trans_translation.pid IN (SELECT id FROM tl_trans_resource WHERE tl_trans_resource.pid = ?)',
            [$projectId],
        );

        // Delete resources
        $this->connection->delete('tl_trans_resource', ['pid' => $projectId]);

        // Delete project
        $this->connection->delete('tl_trans_project', ['id' => $projectId]);

        $json = [
            'status' => 'success',
        ];

        $this->message->addConfirmation(
            $this->translator->trans('CT_TRANS.confirmDeleteProject', [$project->name], 'contao_default')
        );

        return new JsonResponse($json);
    }
}
