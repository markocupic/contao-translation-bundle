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

namespace Markocupic\ContaoTranslationBundle;

use Markocupic\ContaoTranslationBundle\DependencyInjection\Compiler\AddSessionBagsPass;
use Markocupic\ContaoTranslationBundle\DependencyInjection\MarkocupicContaoTranslationExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Class MarkocupicContaoTranslationBundle.
 */
class MarkocupicContaoTranslationBundle extends Bundle
{
    public function getContainerExtension(): MarkocupicContaoTranslationExtension
    {
        return new MarkocupicContaoTranslationExtension();
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new AddSessionBagsPass());
    }
}
