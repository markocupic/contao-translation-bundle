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

use Codefog\HasteBundle\Form\Form;
use Codefog\HasteBundle\UrlParser;
use Contao\Controller;
use Contao\FrontendTemplate;
use Contao\ModuleModel;
use Contao\Template;
use Doctrine\DBAL\Connection;
use Knp\Menu\Matcher\Matcher;
use Knp\Menu\MenuFactory;
use Knp\Menu\Renderer\ListRenderer;
use Markocupic\ContaoTranslationBundle\Model\TransProjectModel;
use Markocupic\ContaoTranslationBundle\Model\TransResourceModel;
use Markocupic\ContaoTranslationBundle\Model\TransTranslationModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

class LanguageController
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TranslatorInterface $translator,
        private readonly UrlParser $urlParser,
        private readonly array $allowedLocales,
    ) {
    }

    public function generate(Template $template, ModuleModel $model, Request $request): string
    {
        if (null === ($project = TransProjectModel::findByPk($request->query->get('project'))) || !$request->query->has('act')) {
            return '';
        }

        $partial = new FrontendTemplate('language_partial');

        // Generate the form
        $partial->form = $this->generateForm($request, $project);

        $stmt = $this->connection->executeQuery(
            'SELECT * FROM tl_trans_language WHERE pid = ?',
            [$project->id]
        );

        $rows = [];

        while (false !== ($row = $stmt->fetchAssociative())) {
            $row['untranslated'] = TransTranslationModel::countUntranslatedByProjectAndLanguage($project, $row['language']);
            $row['total'] = TransTranslationModel::countTranslatedByProjectAndLanguage($project, $project->sourceLanguage);

            if ($row['total'] > 0) {
                $row['perc_translated'] = 100 - ceil($row['untranslated'] / $row['total'] * 100);
            } else {
                $row['perc_translated'] = '-';
            }

            // Add translation links
            $factory = new MenuFactory();
            $menu = $factory->createItem('TranslationMenu');
            $menu->setChildrenAttribute('class', 'trans-menu');

            if (null !== ($resources = TransResourceModel::findByPid($project->id))) {
                while ($resources->next()) {
                    $menu->addChild(
                        $resources->name.' '.$this->getPercentageTranslated($resources->current(), $row['language']),
                        [
                            'uri' => $this->urlParser->addQueryString(
                                sprintf(
                                    'act=translate&language=%s&resource=%s',
                                    $row['language'],
                                    $resources->id,
                                )
                            ),
                        ]
                    );
                }
            }

            $renderer = new ListRenderer(new Matcher());
            $row['trans_nav'] = $renderer->render($menu);

            $rows[] = $row;
        }

        $partial->languages = $rows;
        $partial->project = $project->row();

        return $partial->parse();
    }

    public function generateForm(Request $request, TransProjectModel $project): string
    {
        $form = new Form(
            'addLanguageForm',
            'POST',
            static fn ($objHaste) => $request->request->get('FORM_SUBMIT') === $objHaste->getFormId()
        );

        $form->addFormField('locales', [
            'label' => $this->translator->trans('CT_TRANS.addLocale', [], 'contao_default'),
            'inputType' => 'select',
            'options' => $this->allowedLocales,
            'eval' => [
                'mandatory' => true,
            ],
        ]);

        $form->addFormField('submit', [
            'label' => $this->translator->trans('CT_TRANS.submitLbl', [], 'contao_default'),
            'inputType' => 'submit',
        ]);

        if ($form->validate()) {
            $value = $form->fetch('locales');

            $result = $this->connection->fetchOne('SELECT * FROM tl_trans_language WHERE language = ? AND pid = ?', [$value, $project->id]);

            if (!$result) {
                $set = [
                    'pid' => $project->id,
                    'tstamp' => time(),
                    'language' => $value,
                ];

                $this->connection->insert('tl_trans_language', $set);
            }

            Controller::reload();
        }

        return $form->generate();
    }

    private function getPercentageTranslated(TransResourceModel $resource, string $language): string
    {
        $untranslated = TransTranslationModel::countUntranslatedByResourceAndLanguage($resource, $language);
        $total = TransTranslationModel::countTranslatedByResourceAndLanguage($resource, $resource->getRelated('pid')->sourceLanguage);

        $translated = '-';

        if ($total > 0) {
            $translated = (string) ceil(100 - ($untranslated / $total * 100));
        }

        return sprintf(
            '(%s: %s %%)',
            $this->translator->trans('CT_TRANS.translated', [], 'contao_default'),
            $translated,
        );
    }
}
