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

    #[Route('/category', name:'category_add', methods:['POST'])]
    public function add(Request $r, EntityManagerInterface $em, ValidatorInterface $v) : Response
    {
        // On récupère les infos envoyées en header
        $headers = $r->headers->all();
        // Si la clé 'token' existe et qu'elle n'est pas vide dans le header
        if(isset($headers['token']) && !empty($headers['token'])){
            $jwt = current($headers['token']); // Récupère la cellule 0 avec current()
            $key = $this->getParameter('jwt_secret');

            // On essaie de décoder le jwt
            try{
                $decoded = JWT::decode($jwt, new Key($key, 'HS256'));
            }
            // Si la signature n'est pas vérifiée ou que la date d'expiration est passée, il entrera dans le catch
            catch(\Exception $e){
                return new JsonResponse($e->getMessage(), 403);
            }

            // On regarde si la clé 'roles' existe et si l'utilisateur possède le bon rôle
            if($decoded->roles != null  && in_array('ROLE_ADMIN', $decoded->roles)){

                $category = new Category();
                $category->setTitle($r->get('title')); // Récupère le paramètre 'name' de la requête et l'assigne à l'objet

                $errors = $v->validate($category); // Vérifie que l'objet soit conforme avec les validations (assert)
                if(count($errors) > 0){
                    // S'il y a au moins une erreur
                    $e_list = [];
                    foreach($errors as $e){ // On parcours toutes les erreurs
                        $e_list[] = $e->getMessage(); // On ajoute leur message dans le tableau de messages
                    }

                    return new JsonResponse($e_list, 400); // On retourne le tableau de messages
                }

                $em->persist($category);
                $em->flush();

                return new JsonResponse('success', 201);

            }
        }

        return new JsonResponse('Access denied', 403);
    }

    #[Route('/category/{id}', name:'category_update', methods:['PATCH'])]
    public function update(Category $category = null, Request $r, ValidatorInterface $v, EntityManagerInterface $em) : Response
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
                
                if($category === null){
                    return new JsonResponse('Catégorie introuvable', 404); // Retourne un status 404 car le 204 ne retourne pas de message
                }

                $params = 0;
                // On regarde si l'attribut name reçu n'est pas null
                if($r->get('title') != null){
                    $params++;
                    // On attribue à la category le nouveau name
                    $category->setTitle($r->get('title'));
                }

                if($params > 0){
                    $errors = $v->validate($category); // Vérifie que l'objet soit conforme avec les validations (assert)
                    if(count($errors) > 0){
                        // S'il y a au moins une erreur
                        $e_list = [];
                        foreach($errors as $e){ // On parcours toutes les erreurs
                            $e_list[] = $e->getMessage(); // On ajoute leur message dans le tableau de messages
                        }

                        return new JsonResponse($e_list, 400); // On retourne le tableau de messages
                    }

                    // Si tout va bien, on sauvegarde
                    $em->persist($category);
                    $em->flush();
                }
                else{
                    return new JsonResponse('Empty', 200);
                }

                return new JsonResponse('Success', 200);
            }
        }
        return new JsonResponse('Access denied', 403);
    }

    #[Route('/category/{id}', name:'category_delete', methods:['DELETE'])]
    public function delete(Category $category = null, EntityManagerInterface $em, Request $request): Response
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

                if($category == null){
                    return new JsonResponse('Catégorie introuvable', 404);
                }

                $em->remove($category);
                $em->flush();

                return new JsonResponse('Catégorie supprimée', 204);
            }
        }
        return new JsonResponse('Access denied', 403);
    }
}