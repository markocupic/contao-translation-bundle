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

namespace Markocupic\ContaoTranslationBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\ServiceAnnotation\FrontendModule;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial\CreateNewProjectController;
use Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial\LanguageController;
use Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial\MenuController;
use Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial\ProjectController;
use Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial\ResourceController;
use Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial\TranslateController;
use Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial\UploadController;
use Markocupic\ContaoTranslationBundle\Message\Message;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class TranslationModuleController.
 *
 * @FrontendModule(TranslationModuleController::TYPE, category="translation", template="mod_translation_module")
 */
class TranslationModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'translation_module';
    private Message $message;
    private MenuController $menuController;
    private ResourceController $resourceController;
    private UploadController $uploadController;
    private LanguageController $languageController;
    private TranslateController $translateController;
    private CreateNewProjectController $createNewProjectController;
    private ProjectController $projectController;
    protected ?PageModel $page = null;

    public function __construct(Message $message, MenuController $menuController, ResourceController $resourceController, UploadController $uploadController, LanguageController $languageController, TranslateController $translateController, CreateNewProjectController $createNewProjectController, ProjectController $projectController)
    {
        $this->message = $message;
        $this->menuController = $menuController;
        $this->resourceController = $resourceController;
        $this->uploadController = $uploadController;
        $this->languageController = $languageController;
        $this->translateController = $translateController;
        $this->createNewProjectController = $createNewProjectController;
        $this->projectController = $projectController;
    }

    /**
     * This method extends the parent __invoke method,
     * its usage is usually not necessary.
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Get the page model
        $this->page = $page;

        if ($this->page instanceof PageModel && $this->get('contao.routing.scope_matcher')->isFrontendRequest($request)) {
            // If TL_MODE === 'FE'
            $this->page->loadDetails();
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * Generate the module.
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): ?Response
    {
        $template->content = '';

        $template->menu = $this->menuController->generate($template, $model, $request);

        if ($request->query->has('act')) {
            $act = $request->query->get('act');

            if (null !== $this->{$act.'Controller'}) {
                $template->content = $this->{$act.'Controller'}->generate($template, $model, $request);
            }
        }

        $template->messages = $this->message->generate();

        return $template->getResponse();
    }
}
