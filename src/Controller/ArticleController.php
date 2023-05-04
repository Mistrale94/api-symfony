<?php

namespace App\Controller;

use App\Entity\Article;
use App\Service\Validator;
use App\Entity\Category;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

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

    #[Route('/article', name:'article_add', methods:['POST'])]
    public function add(Request $request, EntityManagerInterface $em, Validator $validator, UserRepository $userRepository): Response
    {
        $headers = $request->headers->all();
        if (isset($headers['token']) && !empty($headers['token'])) {
            $jwt = current($headers['token']);
            $key = $this->getParameter('jwt_secret');

            try {
                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
            } catch (\Exception $e) {
                return new JsonResponse($e->getMessage(), 403);
            }

            if ($decoded->roles != null && in_array('ROLE_ADMIN', $decoded->roles)) {
                $author = $userRepository->find($decoded->id);
                if ($author === null) {
                    return new JsonResponse('Author not found', 404);
                }

                $article = new Article();
                $article->setTitle($request->get('title'))
                    ->setContent($request->get('content'))
                    ->setAuthor($author)
                    ->setState($request->get('state'))
                    ->setCreated(new \DateTimeImmutable());
                    
                // Essaie de récupérer en base la catégory qui correspond au paramètre reçu
                $category = $em->getRepository(Category::class)->findOneBy(['id' => $request->get('category')]);
        
                if($category == null){
                    return new JsonResponse('Catégorie introuvable', 404);
                }
        
                $article->setCategory($category);
        
                $isValid = $validator->isValid($article);
                if($isValid !== true){
                    return new JsonResponse($isValid, 400);
                }
        
                $em->persist($article);
                $em->flush();

                return new JsonResponse('ok', 200);
            }
        }

        return new JsonResponse('Access denied', 403);
    }

    #[Route('/article/{id}', name:'article_update', methods:['PATCH'])]
    public function update(Article $article = null, Request $r, EntityManagerInterface $em, Validator $v) : Response
    {
        $headers = $r->headers->all();
        if (isset($headers['token']) && !empty($headers['token'])) {
            $jwt = current($headers['token']);
            $key = $this->getParameter('jwt_secret');

            try {
                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
            } catch (\Exception $e) {
                return new JsonResponse($e->getMessage(), 403);
            }

            if ($decoded->roles != null && in_array('ROLE_ADMIN', $decoded->roles)) {
            
                if($article == null){
                    return new JsonResponse('Produit introuvable', 404);
                }

                $params = 0;

                // Si on a reçu une catégorie en paramètres
                if($r->get('category') != null){
                    // On regarde en base si elle existe
                    $category = $em->getRepository(Category::class)->findOneBy(['id' => $r->get('category')]);

                    // Si elle n'existe pas
                    if($category == null){
                        // On retourne une erreur
                        return new JsonResponse('Catégorie introuvable', 404);
                    }

                    $params++;
                    // Si elle existe, on l'attribue au produit
                    $article->setCategory($category);
                }

                if($r->get('title') != null){
                    $params++;
                    $article->setTitle($r->get('title'));
                }
                if($r->get('content') != null){
                    $params++;
                    $article->setContent($r->get('content'));
                }
                if($r->get('state') != null){
                    $params++;
                    $article->setState($r->get('state'));
                }
                if($r->get('state') == 1){
                    $article->setActiveDate(new \DateTimeImmutable());
                }

                if($params > 0){
                    $isValid = $v->isValid($article);
                    if($isValid !== true){
                        return new JsonResponse($isValid, 400);
                    }

                    $em->persist($article);
                    $em->flush();

                    return new JsonResponse('ok', 200);
                }
                else{
                    return new JsonResponse('Aucune donnée reçue', 200);
                }
            }
        }
        return new JsonResponse('Access denied', 403);
    }

    #[Route('/article/{id}', name:'article_delete', methods:['DELETE'])]
    public function delete(Article $article = null, Request $request, EntityManagerInterface $em): Response
    {
        $headers = $request->headers->all();
        if (isset($headers['token']) && !empty($headers['token'])) {
            $jwt = current($headers['token']);
            $key = $this->getParameter('jwt_secret');

            try {
                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
            } catch (\Exception $e) {
                return new JsonResponse($e->getMessage(), 403);
            }

            if ($decoded->roles != null && in_array('ROLE_ADMIN', $decoded->roles)) {
                if($article == null){
                    return new JsonResponse('Article introuvable', 404);
                }
        
                $em->remove($article);
                $em->flush();
        
                return new JsonResponse('Article supprimé', 200);
            }
        }
        return new JsonResponse('Access denied', 403);
    }
}