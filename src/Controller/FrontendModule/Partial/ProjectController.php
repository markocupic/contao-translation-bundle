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
use Contao\Template;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Haste\Util\Url;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\ListRenderer;
use Markocupic\ContaoTranslationBundle\Export\ExportFromDb;
use Markocupic\ContaoTranslationBundle\Form\ProjectForm;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectController
{
    private Connection $connection;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;
    private ProjectForm $projectForm;
    private ExportFromDb $exportFromDb;
    private array $allowedLocales;
    private string $projectDir;

    public function __construct(Connection $connection, RequestStack $requestStack, TranslatorInterface $translator, ProjectForm $projectForm, ExportFromDb $exportFromDb, array $allowedLocales, string $projectDir)
    {
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->projectForm = $projectForm;
        $this->exportFromDb = $exportFromDb;
        $this->allowedLocales = $allowedLocales;
        $this->projectDir = $projectDir;
    }

    public function generate(Template $template, ModuleModel $model, Request $request): string
    {
        if (('project' === $request->query->get('act') || $request->query->has('project')) && null === ($project = TransProjectModel::findByPk($request->query->get('project')))) {
            $url = Url::removeQueryString($request->query->keys());
            Controller::redirect($url);
        }

        $partial = new FrontendTemplate('project_partial');

        // Generate the form
        $partial->form = $this->generateForm($request);

        // Generate the menu
        $factory = new MenuFactory();
        $menu = $factory->createItem('DeleteMenu');
        $menu->setChildrenAttribute('class', 'trans-menu');

        $href = '/trans_api/project/delete/'.$project->id;
        $menu->addChild(
            $this->translator->trans('CT_TRANS.deleteProject', [$project->name], 'contao_default'),
            ['uri' => $href]
        )->setAttribute('data-ajax-href', $href);

        $renderer = new ListRenderer(new Matcher());
        $partial->menu = $renderer->render($menu);

        $partial->project = $project->row();

        return $partial->parse();
    }

    /**
     * @throws Exception
     */
    private function generateForm(Request $request): string
    {
        $request = $this->requestStack->getCurrentRequest();

        $model = TransProjectModel::findByPk($request->query->get('project'));

        $form = $this->projectForm->getForm($model);

        if ($form->validate()) {
            if (!is_dir($this->projectDir.'/'.$model->languageFilesFolder)) {
                $widget = $form->getWidget('languageFilesFolder');
                $widget->addError($this->translator->trans('CT_TRANS.invalidLanguageFilesFolder', [], 'contao_default'));
            } else {
                $model->tstamp = time();
                $model->save();
                Controller::reload();
            }
        }

        return $form->generate();
    }
}
