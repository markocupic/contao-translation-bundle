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

namespace Markocupic\ContaoTranslationBundle\Controller\Api;

use Contao\CoreBundle\Framework\ContaoFramework;
use Markocupic\ContaoTranslationBundle\Model\TransResourceModel;
use Markocupic\ContaoTranslationBundle\Model\TransTranslationModel;
use Markocupic\ContaoTranslationBundle\TranslationTable\TranslationTable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;

class TranslationTableController
{
    use AuthorizationTrait;

    private ContaoFramework $contaoFramework;
    private RequestStack $requestStack;
    private TranslationTable $translationTable;

    public function __construct(ContaoFramework $contaoFramework, RequestStack $requestStack, TranslationTable $translationTable)
    {
        $this->contaoFramework = $contaoFramework;
        $this->requestStack = $requestStack;
        $this->translationTable = $translationTable;
    }

    /**
     * @Route("/trans_api/translation_table/get_rows/{resourceId}/{language}",
     *     name="markocupic_contao_translation_api_get_rows",
     *     defaults={
     *         "_scope" = "frontend",
     *         "_token_check" = true
     *     }
     * )
     *
     * @throws \Exception
     */
    public function getRows(int $resourceId, string $language): JsonResponse
    {
        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);

        if (null === ($resource = TransResourceModel::findByPk($resourceId))) {
            $json = [
                'status' => 'error',
                'message' => 'Resource not found.',
            ];

            return new JsonResponse($json);
        }

        if (null === ($project = $resource->getRelated('pid'))) {
            $json = [
                'status' => 'error',
                'message' => 'Project not found.',
            ];

            return new JsonResponse($json);
        }

        $json = [
            'status' => 'success',
            'data' => [
                'rows' => $this->translationTable->getRows($project, $resource, $language),
            ],
        ];

        return new JsonResponse($json);
    }

    /**
     * @Route("/trans_api/translation_table/get_target_source_value/{resourceId}/{language}",
     *     name="markocupic_contao_translation_api_get_target_source_value",
     *     defaults={
     *         "_scope" = "frontend",
     *         "_token_check" = true
     *     }
     * )
     *
     * @throws \Exception
     */
    public function getTargetSourceValue(int $resourceId, string $language): JsonResponse
    {
        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);

        $request = $this->requestStack->getCurrentRequest();

        if (null === TransResourceModel::findByPk($resourceId)) {
            $json = [
                'status' => 'error',
                'message' => 'Resource not found.',
            ];

            return new JsonResponse($json);
        }

        $sourceTranslation = TransTranslationModel::findByPk($request->request->get('sourceId'));

        if (null === $sourceTranslation) {
            $json = [
                'status' => 'error',
                'message' => 'Source not found.',
            ];

            return new JsonResponse($json);
        }

        $json = [
            'status' => 'success',
            'value' => $this->translationTable->getTargetSourceValue($sourceTranslation, $language),
        ];

        return new JsonResponse($json);
    }

    /**
     * @Route("/trans_api/translation_table/update_row/{resourceId}/{language}",
     *     name="markocupic_contao_translation_api_update_row",
     *     defaults={
     *         "_scope" = "frontend",
     *         "_token_check" = true
     *     }
     * )
     *
     * @throws \Exception
     */
    public function updateRow(int $resourceId, string $language): JsonResponse
    {
        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);

        $request = $this->requestStack->getCurrentRequest();

        $value = trim((string) $request->request->get('value'));

        if (null === TransResourceModel::findByPk($resourceId)) {
            $json = [
                'status' => 'error',
                'message' => 'Resource not found.',
            ];

            return new JsonResponse($json);
        }

        $sourceTranslation = TransTranslationModel::findByPk($request->request->get('sourceId'));

        if (null === $sourceTranslation) {
            $json = [
                'status' => 'error',
                'message' => 'Source not found.',
            ];

            return new JsonResponse($json);
        }

        $success = $this->translationTable->update($sourceTranslation, $language, $value);

        $json = [
            'status' => $success ? 'success' : 'error',
            'message' => !$success ? 'Could not update source.' : '',
        ];

        return new JsonResponse($json);
    }
}
