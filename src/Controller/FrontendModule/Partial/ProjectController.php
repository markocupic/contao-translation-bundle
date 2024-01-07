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
use Doctrine\DBAL\Exception;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\ListRenderer;
use Markocupic\ContaoTranslationBundle\Form\ProjectForm;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Util\StrUtil;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProjectController
{
    public function __construct(
        private readonly ProjectForm $projectForm,
        private readonly RequestStack $requestStack,
        private readonly StrUtil $strUtil,
        private readonly TranslatorInterface $translator,
        private readonly UrlParser $urlParser,
        private readonly string $projectDir,
    ) {
    }

    public function generate(Template $template, ModuleModel $model, Request $request): string
    {
        if (('project' === $request->query->get('act') || $request->query->has('project')) && null === ($project = TransProjectModel::findByPk($request->query->get('project')))) {
            $url = $this->urlParser->removeQueryString($request->query->keys());
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
        $menu
            ->addChild(
                $this->translator->trans('CT_TRANS.deleteProject', [$project->name], 'contao_default'),
                ['uri' => $href],
            )
            ->setAttribute('data-ajax-href', $href)
        ;

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
            $hasError = false;

            $path = $model->languageFilesFolder;
            $path = $this->strUtil->sanitizeFolderDirectoryName($path);
            $path = Path::canonicalize($this->projectDir.'/'.$path);
            $model->languageFilesFolder = Path::makeRelative($path, $this->projectDir);

            if (empty($path) || !is_dir($path)) {
                $hasError = true;
                $widget = $form->getWidget('languageFilesFolder');
                $widget->addError($this->translator->trans('CT_TRANS.invalidLanguageFilesFolder', [], 'contao_default'));
            }

            if (!$hasError) {
                $model->tstamp = time();
                $model->save();
                Controller::reload();
            }
        }

        return $form->generate();
    }
}
