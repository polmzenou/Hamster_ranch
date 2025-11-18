<?php

namespace App\EventListener;

use App\Entity\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Bundle\SecurityBundle\Security;

class GoldCheckListener implements EventSubscriberInterface
{
    public function __construct(private Security $security)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        // Exclure les routes publiques
        if (str_starts_with($path, '/api/register') || 
            str_starts_with($path, '/api/login') ||
            str_starts_with($path, '/_')) {
            return;
        }

        // Vérifier uniquement les routes API
        if (!str_starts_with($path, '/api')) {
            return;
        }

        $user = $this->security->getUser();

        // Si pas d'utilisateur connecté, on laisse passer (la sécurité gérera l'authentification)
        if (!$user instanceof User) {
            return;
        }

        // Les administrateurs ne sont pas affectés par cette règle
        if (in_array('ROLE_ADMIN', $user->getRoles())) {
            return;
        }

        // Vérifier si l'utilisateur a moins de 0 gold
        if ($user->getGold() < 0) {
            $event->setResponse(new JsonResponse([
                'error' => 'Vous avez perdu ! Votre solde de gold est négatif. Vous ne pouvez plus effectuer d\'actions.'
            ], 400));
        }
    }
}

