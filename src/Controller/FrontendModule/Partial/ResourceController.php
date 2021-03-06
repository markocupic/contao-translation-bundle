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
use Haste\Util\Url;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\ListRenderer;
use Markocupic\ContaoTranslationBundle\Export\ExportFromDb;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Session\SessionConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class ResourceController
{
    private Connection $connection;
    private TranslatorInterface $translator;
    private ExportFromDb $exportFromDb;
    private UploadController $uploadController;
    private string $projectDir;

    public function __construct(Connection $connection, TranslatorInterface $translator, ExportFromDb $exportFromDb, UploadController $uploadController, string $projectDir)
    {
        $this->connection = $connection;
        $this->translator = $translator;
        $this->exportFromDb = $exportFromDb;
        $this->uploadController = $uploadController;
        $this->projectDir = $projectDir;
    }

    public function generate(Template $template, ModuleModel $model, Request $request): string
    {
        if ('export' === $request->query->get('do') && null !== ($project = TransProjectModel::findByPk($request->query->get('project')))) {
            $repoImport = $request->query->has('repo_import');
            $this->exportFromDb->export($project, $repoImport);
        }

        if (null === ($project = TransProjectModel::findByPk($request->query->get('project'))) || !$request->query->has('act')) {
            $url = Url::removeQueryString($request->query->keys());
            Controller::redirect($url);
        }

        $partial = new FrontendTemplate('resource_partial');

        $partial->upload = $this->uploadController->generate($template, $model, $request);

        $rows = [];

        $stmt = $this->connection->executeQuery(
            'SELECT * FROM tl_trans_resource WHERE pid = ?',
            [$project->id]
        );

        while (false !== ($row = $stmt->fetchAssociative())) {
            $factory = new MenuFactory();
            $menu = $factory->createItem('ResourceMenu');
            $menu->setChildrenAttribute('class', 'trans-menu');

            $href = '/trans_api/resource/delete/'.$row['id'];
            $menu
                ->addChild($this->translator->trans('CT_TRANS.delete', [], 'contao_default'), ['uri' => $href])
                ->setAttribute('data-ajax-href', $href)
            ;

            $renderer = new ListRenderer(new Matcher());
            $row['menu'] = $renderer->render($menu);

            $rows[] = $row;
        }

        $partial->resources = $rows;
        $partial->import_resources_from_path_menu = false;

        if (!empty($project->languageFilesFolder)) {
            if (is_dir($this->projectDir.'/'.$project->languageFilesFolder)) {
                $factory = new MenuFactory();
                $menu = $factory->createItem('importLangFilesFromPathMenu');
                $menu->setChildrenAttribute('class', 'trans-menu');

                $sessionBag = $request->getSession()->getBag(SessionConfig::BAG_NAME);
                $href = '/trans_api/resource/import_resources_from_path/'.$project->id;
                $href = Url::addQueryString('authToken='.$sessionBag->get('authToken'), $href);
                $menu->addChild($this->translator->trans('CT_TRANS.importLangFilesFromPath', [$project->languageFilesFolder], 'contao_default'), ['uri' => $href])
                    ->setAttribute('data-ajax-href', $href)
                ;

                $href = Url::addQueryString('do=export&repo_import=true');
                $menu->addChild($this->translator->trans('CT_TRANS.exportLangFilesToPath', [], 'contao_default'), ['uri' => $href]);

                $href = Url::addQueryString('do=export');
                $menu->addChild($this->translator->trans('CT_TRANS.downloadLangFiles', [], 'contao_default'), ['uri' => $href]);

                $renderer = new ListRenderer(new Matcher());
                $partial->import_resources_from_path_menu = $renderer->render($menu);
            }
        }

        $partial->project = $project->row();

        return $partial->parse();
    }
}
