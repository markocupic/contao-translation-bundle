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

namespace Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial;

use Contao\Controller;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Haste\Form\Form;
use Markocupic\ContaoTranslationBundle\Import\DbImport;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Upload\FileUpload;
use Ramsey\Uuid\Uuid;
use Safe\Exceptions\FilesystemException;
use Symfony\Contracts\Translation\TranslatorInterface;
use function Safe\mkdir;
use Symfony\Component\HttpFoundation\Request;

class UploadController
{
    private DbImport $dbImport;
    private FileUpload $fileUpload;
    private TranslatorInterface $translator;
    private string $projectDir;

    public function __construct(DbImport $dbImport, FileUpload $fileUpload, TranslatorInterface $translator, string $projectDir)
    {
        $this->dbImport = $dbImport;
        $this->fileUpload = $fileUpload;
        $this->translator = $translator;
        $this->projectDir = $projectDir;
    }

    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): void
    {
    }

    public function generate(Template $template, ModuleModel $model, Request $request): string
    {
        if (null === ($project = TransProjectModel::findByPk($request->query->get('project')))) {
            return '';
        }

        $partial = new FrontendTemplate('upload_partial');

        $partial->form = $this->generateUploadForm($request, $project);

        return $partial->parse();
    }

    /**
     * @throws FilesystemException
     */
    private function generateUploadForm(Request $request, TransProjectModel $project): string
    {
        $uuid = Uuid::uuid4()->toString();
        $uploadFolder = $this->projectDir.'/system/tmp/'.$uuid;

        $form = new Form(
            'transUpload',
            'POST',
            static fn ($objHaste) => $request->request->get('FORM_SUBMIT') === $objHaste->getFormId()
        );

        $form->addFormField('file', [
            'label' => $this->translator->trans('CT_TRANS.selectFiles', [], 'contao_default'),
            'inputType' => 'upload',
            'eval' => [
                'extensions' => 'xlf',
                'mandatory' => true,
            ],
        ]);

        $form->addFormField('submit', [
            'label' => $this->translator->trans('CT_TRANS.fileUploadSubmitLbl', [], 'contao_default'),
            'inputType' => 'submit',
            'ignoreModelValue' => true,
        ]);

        // Custom template has to be set
        // after the last widget has been added to the form.
        $form
            ->getWidget('file')
            ->template = 'form_upload_trans_multifile'
        ;

        if ($request->request->get('FORM_SUBMIT') === $form->getFormId() && isset($_FILES['file']) && !empty($_FILES['file'])) {
            mkdir($uploadFolder);

            $arrFiles = $this->fileUpload->getFilesFromGlobal('file');

            if (!empty($arrFiles)) {
                $arrUploadedFiles = $this->fileUpload->moveUploadedFiles($arrFiles, $uploadFolder);
                $this->dbImport->import($arrUploadedFiles, $project);
            }
            Controller::reload();
        }

        return $form->generate();
    }
}
