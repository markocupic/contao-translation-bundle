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

namespace Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial;

use Contao\Controller;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Haste\Util\Url;
use Markocupic\ContaoTranslationBundle\Form\ProjectForm;
use Markocupic\ContaoTranslationBundle\Message\Message;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class CreateNewProjectController
{
    private Connection $connection;
    private ProjectForm $projectForm;
    private TranslatorInterface $translator;
    private Message $message;
    private array $allowedLocales;

    public function __construct(Connection $connection, ProjectForm $projectForm, TranslatorInterface $translator, Message $message, array $allowedLocales)
    {
        $this->connection = $connection;
        $this->projectForm = $projectForm;
        $this->translator = $translator;
        $this->message = $message;
        $this->allowedLocales = $allowedLocales;
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

        if ($form->validate()) {
            $name = $form->fetch('name');

            if (
                !$this->connection->fetchOne(
                    'SELECT id FROM tl_trans_project WHERE name = ?',
                    [$name],
                )
            ) {
                $model->tstamp = time();
                $model->save();
            }

            $this->message->addConfirmation(
                $this->translator->trans('CT_TRANS.confirmCreateProject', [$name], 'contao_default')
            );

            $url = Url::removeQueryString($request->query->keys());
            Controller::redirect($url);
        }

        return $form->generate();
    }
}
