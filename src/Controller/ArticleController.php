<?php

namespace App\Controller;

use App\Entity\Article;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ArticleController extends AbstractController
{
    #[Route('/articles', name: 'app_articles', methods:['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $articles = $em->getRepository(Article::class)->findAll();

        return new JsonResponse($articles);
    }

    #[Route('/article/{id}', name:'one_article', methods:['GET'])]
    public function one_article($id, EntityManagerInterface $em): Response
    {
        $article = $em->getRepository(Article::class)->findOneById($id);

        if($article == null){
            return new JsonResponse('Article introuvable', 404);
        }

        return new JsonResponse($article, 200);
    }
}