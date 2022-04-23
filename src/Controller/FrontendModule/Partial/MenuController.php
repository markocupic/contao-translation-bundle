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

use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Haste\Util\Url;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\ListRenderer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class MenuController
{
    private Connection $connection;
    private TranslatorInterface $translator;

    public function __construct(Connection $connection, TranslatorInterface $translator)
    {
        $this->connection = $connection;
        $this->translator = $translator;
    }

    public function generate(Template $template, ModuleModel $model, Request $request): string
    {
        $partial = new FrontendTemplate('menu_partial');

        $stmt = $this->connection->executeQuery(
            'SELECT * FROM tl_trans_project'
        );

        $factory = new MenuFactory();

        // Add project list
        $menu = $factory->createItem('TranslationMenu');
        $menu->setChildrenAttribute('class', 'trans-menu');

        // Remove query strings
        $url = Url::removeQueryString($request->query->keys());

        while (false !== ($row = $stmt->fetchAssociative())) {
            $partial->hasProjects = true;
            $level1 = $menu->addChild($row['name'], [
                'uri' => Url::addQueryString('act=project&project='.$row['id'], $url),
            ]);

            if ((int) $request->query->get('project') === (int) $row['id']) {
                $level1->addChild($this->translator->trans('CT_TRANS.languages', [], 'contao_default'), [
                    'uri' => Url::addQueryString('act=language&project='.$row['id'], $url),
                ]);
                $level1->addChild($this->translator->trans('CT_TRANS.resources', [], 'contao_default'), [
                    'uri' => Url::addQueryString('act=resource&project='.$row['id'], $url),
                ]);
            }
        }

        $renderer = new ListRenderer(new Matcher());
        $partial->menu = $renderer->render($menu);

        $partial->href_create_new_project = Url::addQueryString('act=createNewProject');

        return $partial->parse();
    }
}
