<?php

namespace AppBundle\Service\Handler;



use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Component\Form\FormInterface;

class FormHandler
{

    protected $form;
    protected $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function check(FormInterface $form){// FormInterface : type de donnée "formulaire"
        $this->form = $form;
        if($this->form->isValid() && $this->form->isSubmitted())
            return true;

        return false;
    }

    public function process(){

        $em = $this->doctrine->getManager();/*Tout sauf select*/

        $data   = $this->form->getData();

        // Étape 1 : On « persiste » l'entité : créé l'instance de la data - category
        $em->persist($data);

        //flush : exécution des requêtes
        $em->flush();
    }

    public function delete($id, $entite){

        $em = $this->doctrine->getManager();/*Tout sauf select*/
        $rc = $this->doctrine->getRepository($entite);/*select*/
        $entity = $rc->find($id);
        //dump($entity);die();
        $em->remove($entity);

        //flush : exécution des requêtes
        $em->flush();

    }


}