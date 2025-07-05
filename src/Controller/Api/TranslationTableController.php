<?php

declare(strict_types=1);

/*
 * This file is part of Contao Translation Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-translation-bundle
 */

namespace Markocupic\ContaoTranslationBundle\Controller\Api;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\System;
use Doctrine\DBAL\Connection;
use Markocupic\ContaoTranslationBundle\Model\TransResourceModel;
use Markocupic\ContaoTranslationBundle\Model\TransTranslationModel;
use Markocupic\ContaoTranslationBundle\TranslationTable\TranslationTable;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationTableController
{
    use AuthorizationTrait;

    public function __construct(
        private readonly Connection $connection,
        private readonly ContaoFramework $contaoFramework,
        private readonly LockFactory $lockFactory,
        private readonly RequestStack $requestStack,
        private readonly TranslationTable $translationTable,
        private readonly TranslatorInterface $translator,
    ) {
    }

    #[Route('/trans_api/translation_table/get_rows/{resourceId}/{language}', name: 'markocupic_contao_translation_api_get_rows', defaults: ['_scope' => 'frontend', '_token_check' => true])]
    public function getRows(int $resourceId, string $language): JsonResponse
    {
        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);

        if (null === ($resource = TransResourceModel::findById($resourceId))) {
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

    #[Route('/trans_api/translation_table/get_target_source_value/{resourceId}/{language}', name: 'markocupic_contao_translation_api_get_target_source_value', defaults: ['_scope' => 'frontend', '_token_check' => true])]
    public function getTargetSourceValue(int $resourceId, string $language): JsonResponse
    {
        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);

        $request = $this->requestStack->getCurrentRequest();

        if (null === TransResourceModel::findById($resourceId)) {
            $json = [
                'status' => 'error',
                'message' => 'Resource not found.',
            ];

            return new JsonResponse($json);
        }

        $sourceTranslation = TransTranslationModel::findById($request->request->get('sourceId'));

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

    #[Route('/trans_api/translation_table/update_row/{resourceId}/{language}', name: 'markocupic_contao_translation_api_update_row', defaults: ['_scope' => 'frontend', '_token_check' => true])]
    public function updateRow(int $resourceId, string $language): JsonResponse
    {
        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);

        $request = $this->requestStack->getCurrentRequest();

        $value = trim((string) $request->request->get('value'));

        if (null === TransResourceModel::findById($resourceId)) {
            $json = [
                'status' => 'error',
                'message' => 'Resource not found.',
            ];

            return new JsonResponse($json);
        }

        $sourceTranslation = TransTranslationModel::findById($request->request->get('sourceId'));

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

    #[Route('/trans_api/translation_table/insert_new_row', name: 'markocupic_contao_translation_api_insert_new_row', defaults: ['_scope' => 'frontend', '_token_check' => true])]
    public function insertNewRow(): JsonResponse
    {
        $lock = $this->lockFactory->createLock('translation_table_insert_row');
        $lock->acquire();

        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);
        System::loadLanguageFile('default');

        $request = $this->requestStack->getCurrentRequest();

        // Get data from POST
        $sourceId = (int) $request->request->get('sourceId');
        $translationId = trim($request->request->get('translationId'), '.');
        $translationString = $request->request->get('translationString');
        $relatedTranslation = TransTranslationModel::findById($sourceId);
        $language = $relatedTranslation->language;

        if ('' === $translationString) {
            $json = [
                'status' => 'error',
                'message' => $this->translator->trans('CT_TRANS.translationStringCannotBeEmpty', [], 'contao_default'),
            ];

            return new JsonResponse($json);
        }

        if ('' === $translationId) {
            $json = [
                'status' => 'error',
                'message' => $this->translator->trans('CT_TRANS.translationIdCannotBeEmpty', [], 'contao_default'),
            ];

            return new JsonResponse($json);
        }

        if (null === $relatedTranslation) {
            throw new \Exception('No related translation not found.');
        }

        if (null !== TransTranslationModel::findOneByTranslationIdAndLanguage($translationId, $language)) {
            $json = [
                'status' => 'error',
                'message' => $this->translator->trans('CT_TRANS.translationIdAlreadyExists', [$translationId], 'contao_default'),
            ];

            return new JsonResponse($json);
        }

        $this->connection->beginTransaction();

        try {
            $this->resort($relatedTranslation->pid, $relatedTranslation->language);

            $new = new TransTranslationModel();
            $new->tstamp = time();
            $new->pid = $relatedTranslation->pid;
            $new->language = $relatedTranslation->language;
            $new->sorting = $relatedTranslation->sorting + 10;
            $new->translationId = $translationId;
            $new->translation = $translationString;
            $new->save();

            $this->connection->commit();

            // Resort again
            $this->resort($new->pid, $new->language);

            $json = [
                'status' => 'success',
                'message' => 'Successfully inserted new row.',
            ];
        } catch (\throwable $e) {
            $this->connection->rollBack();
            $json = [
                'status' => 'error',
                'message' => method_exists($e, 'getMessage') ? $e->getMessage() : 'Could not insert new row due to an unexpected error.',
            ];
        } finally {
            $lock->release();
        }

        return new JsonResponse($json);
    }

    #[Route('/trans_api/translation_table/delete_row', name: 'markocupic_contao_translation_api_delete_row', defaults: ['_scope' => 'frontend', '_token_check' => true])]
    public function deleteRow(): JsonResponse
    {
        $lock = $this->lockFactory->createLock('translation_table_delete_row');
        $lock->acquire();

        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);
        $request = $this->requestStack->getCurrentRequest();

        // Get data from POST
        $sourceId = (int) $request->request->get('sourceId');
        $sourceTranslation = TransTranslationModel::findById($sourceId);
        $pid = $sourceTranslation->pid;
        $language = $sourceTranslation->language;

        if (null === $sourceTranslation) {
            throw new \Exception('Resource or translation not found.');
        }

        $this->connection->beginTransaction();

        try {
            $this->connection->executeStatement('DELETE FROM tl_trans_translation WHERE pid = ? AND translationId = ?', [$sourceTranslation->pid, $sourceTranslation->translationId]);
            $this->resort($pid, $language);
            $this->connection->commit();
        } catch (\throwable $e) {
            $this->connection->rollBack();
            $json = [
                'status' => 'error',
                'message' => method_exists($e, 'getMessage') ? $e->getMessage() : 'Could not insert new row.',
            ];

            return new JsonResponse($json);
        } finally {
            $lock->release();
        }

        $json = [
            'status' => 'success',
            'message' => 'Successfully deleted translation.',
        ];

        return new JsonResponse($json);
    }

    #[Route('/trans_api/translation_table/change_order', name: 'markocupic_contao_translation_api_change_order', defaults: ['_scope' => 'frontend', '_token_check' => true])]
    public function changeSorting(): JsonResponse
    {
        $lock = $this->lockFactory->createLock('translation_table_cahnge_order');
        $lock->acquire();

        // Throws an exception if client is not authorized
        $this->isAuthorized($this->requestStack);

        $this->contaoFramework->initialize(true);
        $request = $this->requestStack->getCurrentRequest();

        // Get data from POST
        $translationStack = $request->request->get('translationStack');
        $translationStack = json_decode($translationStack, true);

        if (empty($translationStack) && !isset($translationStack[0]['item'])) {
            $json = [
                'status' => 'success',
                'message' => 'Empty translation stack.',
            ];

            return new JsonResponse($json);
        }

        $transResourceModel = TransTranslationModel::findById($translationStack[0]['item'])?->getRelated('pid');

        if (null === $transResourceModel) {
            $json = [
                'status' => 'success',
                'message' => 'Resource not found.',
            ];

            return new JsonResponse($json);
        }

        $arrIDS = [];

        foreach ($translationStack as $value) {
            $arrIDS[] = $value['item'];
        }

        $arrTransIds = [];

        foreach ($arrIDS as $id) {
            if (null === ($translation = TransTranslationModel::findById($id))) {
                continue;
            }
            $arrTransIds[] = $translation->translationId;
        }

        $this->connection->beginTransaction();

        try {
            $this->connection->update('tl_trans_translation', ['sorting' => 0], ['pid' => $transResourceModel->id]);

            $order = 0;

            foreach ($arrTransIds as $transId) {
                $order += 256;
                $this->connection->update('tl_trans_translation', ['sorting' => $order], ['pid' => $transResourceModel->id, 'translationId' => $transId]);
            }
            $this->connection->commit();
        } catch (\throwable $e) {
            $this->connection->rollBack();
            if (method_exists($e, 'getMessage')) {
                $json = [
                    'status' => 'error',
                    'message' => method_exists($e, 'getMessage') ? $e->getMessage() : 'There has been an unexpected error.',
                ];

                return new JsonResponse($json);
            }
        } finally {
            $lock->release();
        }

        $json = [
            'status' => 'success',
            'message' => 'Successfully sorted.',
        ];

        return new JsonResponse($json);
    }

    private function resort(int $pid, string $language): void
    {
        $rows = $this->connection->fetchAllAssociative('SELECT * FROM tl_trans_translation WHERE pid = ? AND language = ? ORDER BY sorting ASC', [$pid, $language]);

        $i = 0;

        foreach ($rows as $row) {
            ++$i;
            $sorting = $i * 256;
            $set = [
                'sorting' => $sorting,
            ];

            $this->connection->update('tl_trans_translation', $set, ['pid' => $row['pid'], 'translationId' => $row['translationId']]);
        }
    }
}
