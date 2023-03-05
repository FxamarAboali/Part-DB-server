<?php
/*
 * This file is part of Part-DB (https://github.com/Part-DB/Part-DB-symfony).
 *
 *  Copyright (C) 2019 - 2023 Jan Böhmer (https://github.com/jbtronics)
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published
 *  by the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Security;

use App\Entity\UserSystem\User;
use Hslavich\OneloginSamlBundle\Security\Http\Authenticator\Token\SamlToken;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnsureSAMLUserForSAMLLoginChecker implements EventSubscriberInterface
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public static function getSubscribedEvents()
    {
        return [
            AuthenticationSuccessEvent::class => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $token = $event->getAuthenticationToken();
        $user = $token->getUser();

        //If we are using SAML, we need to check that the user is a SAML user.
        if ($token instanceof SamlToken) {
            if ($user instanceof User && !$user->isSAMLUser()) {
                throw new CustomUserMessageAccountStatusException($this->translator->trans('saml.error.cannot_login_local_user_per_saml', [], 'security'));
            }
        } else { //Ensure that you can not login locally with a SAML user (even if this should not happen, as the password is not set)
            if ($user instanceof User && $user->isSamlUser()) {
                throw new CustomUserMessageAccountStatusException($this->translator->trans('saml.error.cannot_login_saml_user_locally', [], 'security'));
            }
        }
    }
}