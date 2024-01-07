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

namespace Markocupic\ContaoTranslationBundle\Controller\FrontendModule;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ScopeMatcher;
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
use Markocupic\ContaoTranslationBundle\Session\SessionConfig;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

#[AsFrontendModule(TranslationModuleController::TYPE, category: 'translation', template: 'mod_translation_module')]
class TranslationModuleController extends AbstractFrontendModuleController
{
    public const TYPE = 'translation_module';
    protected PageModel|null $page = null;
    private string|null $authToken = null;

    public function __construct(
        private readonly CreateNewProjectController $createNewProjectController,
        private readonly LanguageController $languageController,
        private readonly MenuController $menuController,
        private readonly Message $message,
        private readonly ProjectController $projectController,
        private readonly ResourceController $resourceController,
        private readonly ScopeMatcher $scopeMatcher,
        private readonly TranslateController $translateController,
        private readonly UploadController $uploadController,
    ) {
    }

    /**
     * This method extends the parent __invoke method,
     * its usage is usually not necessary.
     */
    public function __invoke(Request $request, ModuleModel $model, string $section, array $classes = null, PageModel $page = null): Response
    {
        // Get the page model
        $this->page = $page;

        // If TL_MODE === 'FE'
        if ($this->page instanceof PageModel && $this->scopeMatcher->isFrontendRequest($request)) {
            $this->page->loadDetails();

            /** @var AttributeBagInterface $sessionBag */
            $sessionBag = $request->getSession()->getBag(SessionConfig::BAG_NAME);

            if ($sessionBag->has('authToken')) {
                $this->authToken = $sessionBag->get('authToken');
            } else {
                $this->authToken = bin2hex(openssl_random_pseudo_bytes(256));
                $sessionBag->set('authToken', $this->authToken);
            }
        }

        return parent::__invoke($request, $model, $section, $classes);
    }

    /**
     * Generate the module.
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $template->auth_token = $this->authToken;
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
