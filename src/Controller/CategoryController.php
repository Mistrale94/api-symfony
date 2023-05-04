<?php

namespace App\Controller;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CategoryController extends AbstractController
{
    #[Route('/categories', name: 'app_category', methods:['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Category::class)->findAll();

        return new JsonResponse($categories);
    }

    #[Route('/category/{id}', name:'one_category', methods:['GET'])]
    public function get($id, EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->findOneById($id);

        if($category == null){
            return new JsonResponse('Catégorie introuvable', 404);
        }

        return new JsonResponse($category, 200);
    }
}