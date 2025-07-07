<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        private ValidatorInterface $validator,
        private JWTTokenManagerInterface $jwtManager,
        private UserRepository $userRepository,
        private LoggerInterface $logger
    ) {}

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        try {
            $this->logger->info('=== DEBUT REGISTER ===');
            
            $data = json_decode($request->getContent(), true);
            $this->logger->info('Request data: ' . print_r($data, true));

            // Validation des données d'entrée
            if (!$data) {
                $this->logger->error('Invalid JSON data');
                return $this->json(['error' => 'Données JSON invalides'], Response::HTTP_BAD_REQUEST);
            }

            $requiredFields = ['username', 'firstName', 'lastName', 'email', 'password'];
            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $this->logger->error("Missing field: {$field}");
                    return $this->json(['error' => "Le champ {$field} est requis"], Response::HTTP_BAD_REQUEST);
                }
            }

            $this->logger->info('All required fields present');

            // Vérifier si l'utilisateur existe déjà
            $existingUser = $this->userRepository->findOneBy(['email' => $data['email']]);
            if ($existingUser) {
                $this->logger->info('Email already exists: ' . $data['email']);
                return $this->json(['error' => 'Cet email est déjà utilisé'], Response::HTTP_CONFLICT);
            }

            $existingUsername = $this->userRepository->findOneBy(['username' => $data['username']]);
            if ($existingUsername) {
                $this->logger->info('Username already exists: ' . $data['username']);
                return $this->json(['error' => 'Ce nom d\'utilisateur est déjà utilisé'], Response::HTTP_CONFLICT);
            }

            $this->logger->info('Creating new user');

            // Créer le nouvel utilisateur
            $user = new User();
            $user->setUsername($data['username']);
            $user->setFirstName($data['firstName']);
            $user->setLastName($data['lastName']);
            $user->setEmail($data['email']);
            
            $this->logger->info('User data set, hashing password');
            
            // Hasher le mot de passe
            $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);

            $this->logger->info('Password hashed, validating user');

            // Validation avec les contraintes de l'entité
            $errors = $this->validator->validate($user);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                $this->logger->error('Validation errors: ' . print_r($errorMessages, true));
                return $this->json(['error' => 'Données de validation invalides', 'details' => $errorMessages], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $this->logger->info('User validated, persisting to database');

            // Sauvegarder en base
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            $this->logger->info('User persisted, generating JWT token');

            // Générer le token JWT et le mettre dans un cookie
            $token = $this->jwtManager->create($user);
            
            $this->logger->info('JWT token generated, preparing response');

            // Préparer la réponse avec l'ID après la persistance
            $userId = $user->getId();
            $userIdString = $userId ? (string)$userId : null;

            $response = $this->json([
                'user' => [
                    'id' => $userIdString,
                    'username' => $user->getUsername(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                ]
            ], Response::HTTP_CREATED);

            $this->logger->info('Response prepared, adding cookie');

            // Ajouter le cookie avec le token JWT
            $cookie = Cookie::create('BEARER')
                ->withValue($token)
                ->withExpires(new \DateTime('+1 week'))
                ->withPath('/')
                ->withSecure(true) // HTTPS uniquement
                ->withHttpOnly(true) // Pas accessible via JavaScript
                ->withSameSite('none'); // Pour les requêtes cross-origin

            $response->headers->setCookie($cookie);

            $this->logger->info('=== SUCCESS REGISTER ===');

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('=== ERROR IN REGISTER ===');
            $this->logger->error('Exception: ' . $e->getMessage());
            $this->logger->error('File: ' . $e->getFile() . ':' . $e->getLine());
            $this->logger->error('Stack trace: ' . $e->getTraceAsString());
            
            return $this->json([
                'error' => 'Erreur serveur, veuillez réessayer plus tard',
                'debug' => $e->getMessage() // Retirez ceci en production
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (!$data || empty($data['email']) || empty($data['password'])) {
                return $this->json(['error' => 'Email et mot de passe requis'], Response::HTTP_BAD_REQUEST);
            }

            // Rechercher l'utilisateur par email
            $user = $this->userRepository->findOneBy(['email' => $data['email']]);
            
            if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
                return $this->json(['error' => 'Email ou mot de passe incorrect'], Response::HTTP_UNAUTHORIZED);
            }

            // Générer le token JWT et le mettre dans un cookie
            $token = $this->jwtManager->create($user);

            // Préparer la réponse avec l'ID sécurisé
            $userId = $user->getId();
            $userIdString = $userId ? (string)$userId : null;

            $response = $this->json([
                'user' => [
                    'id' => $userIdString,
                    'username' => $user->getUsername(),
                    'firstName' => $user->getFirstName(),
                    'lastName' => $user->getLastName(),
                    'email' => $user->getEmail(),
                ]
            ]);

            // Ajouter le cookie avec le token JWT
            $cookie = Cookie::create('BEARER')
                ->withValue($token)
                ->withExpires(new \DateTime('+1 week'))
                ->withPath('/')
                ->withSecure(true) // HTTPS uniquement
                ->withHttpOnly(true) // Pas accessible via JavaScript
                ->withSameSite('none'); // Pour les requêtes cross-origin

            $response->headers->setCookie($cookie);

            return $response;

        } catch (\Exception $e) {
            $this->logger->error('Login error: ' . $e->getMessage());
            return $this->json(['error' => 'Erreur serveur, veuillez réessayer plus tard'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/refresh', name: 'refresh', methods: ['POST'])]
    public function refresh(Request $request): JsonResponse
    {
        // Récupérer le token depuis le cookie
        $token = $request->cookies->get('BEARER');
        
        if (!$token) {
            return $this->json(['error' => 'Token manquant'], Response::HTTP_UNAUTHORIZED);
        }

        try {
            // Décoder le token pour récupérer l'utilisateur
            $jwtManager = $this->jwtManager;
            // Note: Il faudrait implémenter la logique de refresh selon vos besoins
            
            return $this->json(['message' => 'Token rafraîchi']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Token invalide'], Response::HTTP_UNAUTHORIZED);
        }
    }

    #[Route('/logout', name: 'logout', methods: ['POST'])]
    public function logout(): JsonResponse
    {
        $response = $this->json(['message' => 'Déconnexion réussie']);

        // Supprimer le cookie en le réexpirant
        $cookie = Cookie::create('BEARER')
            ->withValue('')
            ->withExpires(new \DateTime('-1 day'))
            ->withPath('/')
            ->withSecure(true)
            ->withHttpOnly(true)
            ->withSameSite('none');

        $response->headers->setCookie($cookie);

        return $response;
    }

    #[Route('/me', name: 'me', methods: ['GET'])]
    public function getCurrentUser(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();
        
        if (!$user || !$user instanceof User) {
            return $this->json(['error' => 'Non authentifié'], Response::HTTP_UNAUTHORIZED);
        }

        // Préparer la réponse avec l'ID sécurisé
        $userId = $user->getId();
        $userId = $user->getId();

        return $this->json([
            'user' => [
                'id' => $userId,
                'username' => $user->getUsername(),
                'firstName' => $user->getFirstName(),
                'lastName' => $user->getLastName(),
                'email' => $user->getEmail(),
            ]
        ]);
    }
}