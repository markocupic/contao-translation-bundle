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

namespace Markocupic\ContaoTranslationBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Routing\RoutingPluginInterface;
use Markocupic\ContaoTranslationBundle\MarkocupicContaoTranslationBundle;
use Symfony\Component\Config\Loader\LoaderResolverInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\RouteCollection;

class Plugin implements BundlePluginInterface, RoutingPluginInterface
{
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(MarkocupicContaoTranslationBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }

    /**
     * @throws \Exception
     */
    public function getRouteCollection(LoaderResolverInterface $resolver, KernelInterface $kernel): RouteCollection|null
    {
        return $resolver
            ->resolve(__DIR__.'/../../config/routes.yaml')
            ->load(__DIR__.'/../../config/routes.yaml')
        ;
    }
}
