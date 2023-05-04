<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods:['POST'])]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager, Validator $validator): Response
    {
        $user = new User();
        $user->setEmail($request->get('email'))
            ->setFirstname($request->get('firstname'))
            ->setLastname($request->get('lastname'))
            ->setPlainPassword($request->get('pwd'));

        $isValid = $validator->isValid($user);
        if($isValid !== true){
            return new JsonResponse($isValid, 400);
        }

        $user->setPassword(
            $userPasswordHasher->hashPassword(
                $user,
                $user->getPlainPassword()
            )
        );

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse('Inscription effectuée', 200);
    }
}
