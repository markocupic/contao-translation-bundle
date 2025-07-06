<?php

declare(strict_types=1);

/*
 * This file is part of Contao Translation Bundle.
 *
 * (c) Marko Cupic <m.cupic@gmx.ch>
 * @license MIT
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/markocupic/contao-translation-bundle
 */

namespace Markocupic\ContaoTranslationBundle\Controller\FrontendModule\Partial;

use Codefog\HasteBundle\UrlParser;
use Contao\CoreBundle\Csrf\ContaoCsrfTokenManager;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Model\TransResourceModel;
use Symfony\Component\HttpFoundation\Request;

class TranslateController
{
    public function __construct(
        private readonly Connection             $connection,
        private readonly ContaoCsrfTokenManager $contaoCsrfTokenManager,
        private readonly UrlParser              $urlParser,
        private readonly string                 $contaoCsrfTokenName,
    )
    {
    }

    public function generate(Template $template, ModuleModel $model, Request $request): string
    {
        if (!$request->query->has('language')) {
            return '';
        }

        if (null === ($project = TransProjectModel::findById($request->query->get('project')))) {
            return '';
        }

        if (null === ($resource = TransResourceModel::findById($request->query->get('resource')))) {
            return '';
        }

        $partial = new FrontendTemplate('translate_partial');
        $partial->project = $project->row();
        $partial->language = $request->query->get('language');
        $partial->resource = $resource->row();
        $partial->csrf_token = $this->contaoCsrfTokenManager->getToken($this->contaoCsrfTokenName)->getValue();
        $partial->languageLinks = $this->getLanguageLinks($project, $resource);
        return $partial->parse();
    }

    public function getLanguageLinks(TransProjectModel $project, TransResourceModel $resource): array
    {
        $arrLanguages = $this->connection->fetchFirstColumn('SELECT DISTINCT language FROM tl_trans_translation WHERE pid = ?', [$resource->id]);

        $arrLinks = [];

        foreach ($arrLanguages as $language) {
            $arrLinks[] = [
                'language' => $language,
                'link'     => $this->urlParser->addQueryString(sprintf('act=translate&project=%d&resource=%d&language=%s', $project->id, $resource->id, $language)),
            ];
        }

        return $arrLinks;
    }
}
