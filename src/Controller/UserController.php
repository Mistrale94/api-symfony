<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Firebase\JWT\ExpiredException;

use Firebase\JWT\JWT;

class UserController extends AbstractController
{
    #[Route('/login', name: 'login', methods:['POST'])]
    public function index(
        Request $r, 
        EntityManagerInterface $em, 
        UserPasswordHasherInterface $userPasswordHasher
    ): Response
    {
        // On tente de récupérer un utilisateur grace à son email
        $user = $em->getRepository(User::class)->findOneBy(['email' => $r->get('email')]);
        
        if($user == null){
            return new JsonResponse('Utilisateur introuvable', 404);
        }

        if($r->get('pwd') == null || !$userPasswordHasher->isPasswordValid($user, $r->get('pwd'))){
            return new JsonResponse('Identifiants invalides', 400);
        }

        $key = $this->getParameter('jwt_secret');
        $payload = [
            'iat' => time(), // Issued at (date de création)
            'exp' => time() + 3600, // Expiration (date de création + x secondes)
            'id' => $user->getId(), // Identifiant de l'utilisateur
            'roles' => $user->getRoles(),
            'email' => $user->getEmail()
        ];

        $jwt = JWT::encode($payload, $key, 'HS256');

        return new JsonResponse($jwt, 200);
    }

    private function getUserFromJwt(Request $request, EntityManagerInterface $em): ?User
    {
        $authHeader = $request->headers->get('Authorization');
        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        $jwt = substr($authHeader, 7);
        $key = $this->getParameter('jwt_secret');

        try {
            $decodedPayload = JWT::decode($jwt, $key, ['HS256']);
        } catch (ExpiredException $e) {
            throw new AccessDeniedHttpException('Le token a expiré.');
        } catch (\Exception $e) {
            return null;
        }

        if (!isset($decodedPayload->email)) {
            return null;
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $decodedPayload->email]);

        return $user;
    }
}
