<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\CommentRepository;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Validator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class CommentController extends AbstractController
{
    #[Route('/comment/{article_id}', name: 'app_comment_new', methods: ['GET', 'POST'])]
    public function new(Request $request, CommentRepository $commentRepository, EntityManagerInterface $entityManager, ArticleRepository $articleRepository, UserRepository $userRepository): Response
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

            if ($decoded->roles != null && in_array('ROLE_USER', $decoded->roles)) {
                $author = $userRepository->find($decoded->id);
                if ($author === null) {
                    return new JsonResponse('Author not found', 404);
                }

                $comment = new Comment();
                $comment->setArticle($articleRepository->find($request->get('article_id')));
                $comment->setAuthor($author);
                $comment->setComment($request->get('comment'));
                $comment->setState(true);
                $comment->setCreated(new \DateTimeImmutable());

                $entityManager->persist($comment);
                $entityManager->flush();

                return new JsonResponse([
                    'id' => $comment->getId(),
                    'author' => $comment->getAuthor()->getId(),
                    'comment' => $comment->getComment(),
                    'created' => $comment->getCreated()->format('Y-m-d H:i:s'),
                ]);
            }
        }
        return new JsonResponse('Access denied', 403);
    }
    #[Route('/comment/{id}', name: 'app_comment_update', methods: ['PATCH'])]
    public function update(Comment $comment = null, Request $request, EntityManagerInterface $em, Validator $v){
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

                if($comment == null){
                    return new JsonResponse('Produit introuvable', 404);
                }

                $params = 0;

                if($request->get('state') !== null){
                    $params++;
                    $comment->setState($request->get('state'));
                }

                if($params > 0){
                    $isValid = $v->isValid($comment);
                    if($isValid !== true){
                        return new JsonResponse($isValid, 400);
                    }

                    $em->persist($comment);
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
}