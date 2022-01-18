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

namespace Markocupic\ContaoTranslationBundle\Form;

use Haste\Form\Form;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectForm
{
    private RequestStack $requestStack;
    private TranslatorInterface $translator;
    private array $allowedLocales;

    public function __construct(RequestStack $requestStack, TranslatorInterface $translator, array $allowedLocales)
    {
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->allowedLocales = $allowedLocales;
    }

    public function getForm(TransProjectModel $model): Form
    {
        $request = $this->requestStack->getCurrentRequest();

        $form = new Form(
            'createNewProject',
            'POST',
            static fn ($objHaste) => $request->request->get('FORM_SUBMIT') === $objHaste->getFormId()
        );

        $form->bindModel($model);

        $form->addFieldsFromDca(
            'tl_trans_project',
            static function (&$strField, &$arrDca) {
                // Skip elements without inputType
                if (!isset($arrDca['inputType'])) {
                    return false;
                }

                // Select fields
                if ('name' === $strField || 'sourceLanguage' === $strField || 'languageFilesFolder' === $strField) {
                    // Customize form fields
                    //$arrDca['eval']['mandatory'] = true;

                    return true;
                }

                // you must return true otherwise the field will be skipped
                return false;
            }
        );

        $form->addFormField('submit', [
            'label' => $this->translator->trans('CT_TRANS.submitLbl', [], 'contao_default'),
            'inputType' => 'submit',
            'ignoreModelValue' => true,
        ]);

        return $form;
    }
}
