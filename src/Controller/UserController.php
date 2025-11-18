<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class UserController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator
    ) {}

    /** POST /api/register */
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return $this->json(['error' => 'Missing required fields: email, password'], 400);
        }

        $email = trim($data['email']);
        $password = $data['password'];

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['error' => 'Invalid email format'], 400);
        }

        if (strlen($password) < 8) {
            return $this->json(['error' => 'Password must be at least 8 characters'], 400);
        }

        $existingUser = $this->userRepository->findOneBy(['email' => $email]);
        if ($existingUser) {
            return $this->json(['error' => 'Email already exists'], 409);
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setRoles(json_encode(['ROLE_USER']));
        $user->setGold(500);

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getPropertyPath() . ': ' . $error->getMessage();
            }
            return $this->json(['error' => 'Validation failed', 'details' => $errorMessages], 400);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $this->json($user, 201, [], ['groups' => ['user:read']]);
    }

    /** DELETE /api/delete/{id} */
    #[IsGranted('ROLE_ADMIN')]
    #[Route('/api/delete/{id}', name: 'api_user_delete', methods: ['DELETE'])]
    public function delete(UserRepository $repo, EntityManagerInterface $em, int $id): JsonResponse
    {
        $user = $repo->find($id);
        
        if (!$user) {
            return $this->json([
                'error' => 'User not found'
            ], 404);
        }

        $em->remove($user);
        $em->flush();

        return $this->json([
            'message' => 'User deleted successfully'
        ], 200);
    }

    /** GET /api/user */
    #[Route('/api/user', name: 'api_user', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();
        
        if (!$user) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        return $this->json([
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'gold' => $user->getGold(),
            'hamsters' => array_map(function ($h) {
                return [
                    'id' => $h->getId(),
                    'name' => $h->getName(),
                    'age' => $h->getAge(),
                    'hunger' => $h->getHunger(),
                    'gender' => $h->getGender(),
                    'active' => $h->isActive(),
                ];
            }, $user->getHamsters()->toArray()),
        ]);
    }
}
