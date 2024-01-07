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

namespace Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial;

use Codefog\HasteBundle\UrlParser;
use Contao\Controller;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Markocupic\ContaoTranslationBundle\Form\ProjectForm;
use Markocupic\ContaoTranslationBundle\Message\Message;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Util\StrUtil;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateNewProjectController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly Message $message,
        private readonly ProjectForm $projectForm,
        private readonly StrUtil $strUtil,
        private readonly TranslatorInterface $translator,
        private readonly UrlParser $urlParser,
        private readonly string $projectDir,
    ) {
    }

    public function generate(Template $template, ModuleModel $model, Request $request): string
    {
        $partial = new FrontendTemplate('create_new_project_partial');

        $partial->form = $this->generateForm($request);

        return $partial->parse();
    }

    /**
     * @throws Exception
     */
    private function generateForm(Request $request): string
    {
        $model = new TransProjectModel();

        $form = $this->projectForm->getForm($model);

        $hasError = false;

        if ($form->validate()) {

            $path = $model->languageFilesFolder;
            $path = $this->strUtil->sanitizeFolderDirectoryName($path);
            $path = Path::canonicalize($this->projectDir.'/'.$path);
            $model->languageFilesFolder = Path::makeRelative($path, $this->projectDir);

            // Check if path exists
            if (empty($path) || !is_dir($path)) {
                $hasError = true;
                $widget = $form->getWidget('languageFilesFolder');
                $widget->addError($this->translator->trans('CT_TRANS.invalidLanguageFilesFolder', [], 'contao_default'));
            }

            if (!$hasError) {
                $projectName = $form->fetch('name');

                if (
                    !$this->connection->fetchOne(
                        'SELECT id FROM tl_trans_project WHERE name = ?',
                        [$projectName],
                    )
                ) {
                    $model->tstamp = time();
                    $model->save();
                }

                $this->message->addConfirmation(
                    $this->translator->trans('CT_TRANS.confirmCreateProject', [$projectName], 'contao_default')
                );

                $url = $this->urlParser->removeQueryString($request->query->keys());

                Controller::redirect($url);
            }
        }

        return $form->generate();
    }
}
