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

namespace Markocupic\ContaoTranslationBundle\Controller\Api;

use Contao\CoreBundle\Exception\ResponseException;
use Markocupic\ContaoTranslationBundle\Session\SessionConfig;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

trait AuthorizationTrait
{
    public function isAuthorized(RequestStack $requestStack): bool
    {
        $request = $requestStack->getCurrentRequest();

        $sessionBag = $request
            ->getSession()
            ->getBag(SessionConfig::BAG_NAME)
        ;

        $authToken = $request->get('authToken');
        if (!empty((string) $authToken)) {
            if ($sessionBag->has('authToken') && '' !== $sessionBag->get('authToken')) {
                if ($sessionBag->get('authToken') === $authToken) {
                    return true;
                }
            }
        }

        $json = [
            'status' => 'error',
            'message' => 'Authentication failed due to invalid or empty auth token.',
        ];

        $response = new JsonResponse($json, 401);

        throw new ResponseException($response);
    }
}
