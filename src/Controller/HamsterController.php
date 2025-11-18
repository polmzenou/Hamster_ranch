<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Hamster;
use App\Entity\User;
use App\Repository\HamsterRepository;
use App\Service\HamsterManager;
use Doctrine\ORM\EntityManagerInterface;

final class HamsterController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private HamsterRepository $hamsterRepository,
        private HamsterManager $hamsterManager
    ) {}

    /** GET /api/hamsters */
    #[Route('/api/hamsters', name: 'api_hamsters_list', methods: ['GET'])]
    public function getAllHamsters(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non-autorisé'], 401);
        }

        $listHamster = $user->getHamsters()->toArray();

        return $this->json([
            'listHamster' => $listHamster
        ], context: ['groups' => ['hamster:list']]);
    }

    /** GET /api/hamsters/{id} */
    #[Route('/api/hamsters/{id}', name: 'api_hamsters_detail', methods: ['GET'])]
    public function getHamsterById(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non-autorisé'], 401);
        }

        $hamster = $this->hamsterRepository->find($id);

        if (!$hamster) {
            return $this->json(['error' => 'Hamster non trouvé'], 404);
        }

        // Vérifier si l'utilisateur est admin ou propriétaire
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        if (!$isAdmin && $hamster->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Interdit - Vous ne pouvez voir que vos propres hamsters'], 403);
        }

        return $this->json([
            'hamster' => $hamster
        ], context: ['groups' => ['hamster:read']]);
    }

    /** POST /api/hamsters/reproduce */
    #[Route('/api/hamsters/reproduce', name: 'api_hamsters_reproduce', methods: ['POST'])]
    public function reproduce(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non-autorisé'], 401);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['idHamster1']) || !isset($data['idHamster2'])) {
            return $this->json(['error' => 'Un des idHamsters n\'a pas été trouvé'], 400);
        }

        $hamster1 = $this->hamsterRepository->find($data['idHamster1']);
        $hamster2 = $this->hamsterRepository->find($data['idHamster2']);

        if (!$hamster1 || !$hamster2) {
            return $this->json(['error' => 'Un des hamsters n\'a pas été trouvé'], 404);
        }

        if ($hamster1->getOwner()?->getId() !== $user->getId() || $hamster2->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'l\'un des hamsters n\'appartient pas à l\'utilisateur'], 403);
        }

        // Vérifier que les deux hamsters sont actifs
        if (!$hamster1->isActive()) {
            return $this->json(['error' => 'Le premier hamster n\'est pas actif'], 400);
        }

        if (!$hamster2->isActive()) {
            return $this->json(['error' => 'Le deuxième hamster n\'est pas actif'], 400);
        }

        $result = $this->hamsterManager->reproduce($hamster1, $hamster2, $user);

        if (isset($result['error'])) {
            return $this->json($result, 400);
        }

        return $this->json($result['baby'], 201, [], ['groups' => ['hamster:read']]);
    }

    /** POST /api/hamsters/{id}/feed */
    #[Route('/api/hamsters/{id}/feed', name: 'api_hamsters_feed', methods: ['POST'])]
    public function feed(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non-autorisé'], 401);
        }

        $hamster = $this->hamsterRepository->find($id);
        
        if (!$hamster) {
            return $this->json(['error' => 'Hamster non trouvé '], 404);
        }

        if ($hamster->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Interdit, ce hamster ne vous appartient pas ?'], 403);
        }

        $result = $this->hamsterManager->feed($hamster, $user);

        if (isset($result['error'])) {
            return $this->json($result, 400);
        }

        return $this->json([
            'gold' => $user->getGold(),
            'hamster' => [
                'id' => $hamster->getId(),
                'name' => $hamster->getName(),
                'hunger' => $hamster->getHunger(),
                'age' => $hamster->getAge(),
                'active' => $hamster->isActive()
            ]
        ]);
    }

    /** POST /api/hamsters/{id}/sell */
    #[Route('/api/hamsters/{id}/sell', name: 'api_hamsters_sell', methods: ['POST'])]
    public function sell(int $id): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non-autorisé'], 401);
        }

        $hamster = $this->hamsterRepository->find($id);
        
        if (!$hamster) {
            return $this->json(['error' => 'Hamster non trouvé'], 404);
        }

        if ($hamster->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Interdit'], 403);
        }

        $this->hamsterManager->sell($hamster, $user);

        return $this->json(['message' => 'Hamster vendu avec succès', 'gold' => $user->getGold()]);
    }

    /** POST /api/hamster/sleep/{nbDays} */
    #[Route('/api/hamster/sleep/{nbDays}', name: 'api_hamsters_sleep', methods: ['POST'])]
    public function sleep(int $nbDays): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non-autorisé'], 401);
        }

        if ($nbDays < 0) {
            return $this->json(['error' => 'nbDays doit être positif ou nul'], 400);
        }

        if ($nbDays === 0) {
            return $this->json(['message' => 'Aucun temps passé', 'nbDays' => 0]);
        }

        $hamsters = $user->getHamsters();
        $affectedCount = 0;
        $inactiveCount = 0;

        foreach ($hamsters as $hamster) {
            $hamster->setAge($hamster->getAge() + $nbDays);
            $hamster->setHunger($hamster->getHunger() - $nbDays);

            // Vérifier si le hamster devient inactif
            if ($hamster->getAge() > 500 || $hamster->getHunger() < 0) {
                $hamster->setActive(false);
                $inactiveCount++;
            }
            
            $affectedCount++;
        }

        $this->em->flush();

        return $this->json([
            'message' => 'Tous les hamsters ont vieillis de ' . $nbDays . ' jours',
            'nbDays' => $nbDays,
            'affectedHamsters' => $affectedCount,
            'inactiveHamsters' => $inactiveCount
        ]);
    }

    /** PUT /api/hamsters/{id}/rename */
    #[Route('/api/hamsters/{id}/rename', name: 'api_hamsters_rename', methods: ['PUT'])]
    public function rename(int $id, Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['error' => 'Non-autorisé'], 401);
        }

        $hamster = $this->hamsterRepository->find($id);
        
        if (!$hamster) {
            return $this->json(['error' => 'Hamster non trouvé'], 404);
        }

        // Vérifier si l'utilisateur est admin ou propriétaire
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles());
        if (!$isAdmin && $hamster->getOwner()?->getId() !== $user->getId()) {
            return $this->json(['error' => 'Interdit - Vous ne pouvez renommer que vos propres hamsters'], 403);
        }

        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['name'])) {
            return $this->json(['error' => 'Champ nom manquant'], 400);
        }

        $name = trim($data['name']);
        if (strlen($name) < 2) {
            return $this->json(['error' => 'Le nom doit contenir au moins 2 caractères'], 400);
        }

        // Vérifier si le nom n'a pas changé
        if ($hamster->getName() === $name) {
            return $this->json([
                'message' => 'Nom inchangé',
                'hamster' => $hamster
            ], 200, [], ['groups' => ['hamster:read']]);
        }

        $hamster->setName($name);
        $this->em->flush();

        return $this->json([
            'message' => 'Hamster renommé avec succès',
            'hamster' => $hamster
        ], 200, [], ['groups' => ['hamster:read']]);
    }
}
