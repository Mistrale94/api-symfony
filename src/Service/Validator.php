<?php

namespace App\Service;

use Symfony\Component\Validator\Validator\ValidatorInterface;

class Validator{

	private $v;

	public function __construct(ValidatorInterface $validatorInterface)
	{
		$this->v = $validatorInterface;
	}

	public function isValid($obj){
		$errors = $this->v->validate($obj); // Vérifie que l'objet soit conforme avec les validations (assert)
        
		if(count($errors) > 0){
            // S'il y a au moins une erreur
            $e_list = [];
            foreach($errors as $e){ // On parcours toutes les erreurs
                $e_list[] = $e->getMessage(); // On ajoute leur message dans le tableau de messages
            }

            return $e_list; // On retourne le tableau de messages
        }
		else{
			return true;
		}
	}
}