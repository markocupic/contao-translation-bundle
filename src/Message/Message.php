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

namespace Markocupic\ContaoTranslationBundle\Message;

use Contao\Message as ContaoMessage;

class Message
{
    private const SCOPE = 'FE';

    public function hasInfoMessage(): bool
    {
        return ContaoMessage::hasInfo(self::SCOPE);
    }

    public function addInfo(string $strMsg): void
    {
        ContaoMessage::addInfo($strMsg, self::SCOPE);
    }

    public function hasError(): bool
    {
        return ContaoMessage::hasError(self::SCOPE);
    }

    public function addError(string $strMsg): void
    {
        ContaoMessage::addError($strMsg, self::SCOPE);
    }

    public function hasConfirmation(): bool
    {
        return ContaoMessage::hasConfirmation(self::SCOPE);
    }

    public function addConfirmation(string $strMsg): void
    {
        ContaoMessage::addConfirmation($strMsg, self::SCOPE);
    }

    public function generate(): string
    {
        return ContaoMessage::generate(self::SCOPE);
    }
}
