<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Directory;
use AppBundle\Form\DirectoryType;
use AppBundle\Repository\DirectoryRepository;
use AppBundle\Service\Utils\SlugUtils;
use AppBundle\Service\Utils\UploadUtils;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class DirectoryController extends Controller
{
    /**
     * @Route("/admin/directories", name="app.admin.directories.index")
     */
    public function indexAction(Request $request)
    {
        $directories = $this->getDoctrine()->getRepository("AppBundle:Directory")->findAll();

        return $this->render('admin/directory/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'directories' => $directories
        ]);
    }

    /**
     * @Route("/directory/add", name="app.directory.form")
     * @Route("/admin/directory/add", name="app.admin.directory.form")
     * @Route("/admin/directory/update/{id}", name="app.admin.directory.form.update", requirements={"id" = "\d+"})
     */
    public function formAction(Request $request, $id=null)
    {
        $doctrine = $this->getDoctrine();
        $rc = $doctrine->getRepository('AppBundle:Directory');//select
        // creation d'un formulaire : on a l'entité et la class du formulaire... recette de cuisine !
        $entity = $id ? $rc->find($id) : new Directory();
        $entityType     = DirectoryType::class;
        $form = $this->createForm($entityType, $entity);
        $form->handleRequest($request);//récupération de la saisie
        //Service de gestion du formulaire
        $directoryHandler = $this->get('app.service.handler.directoryhandler');
        if($directoryHandler->check($form))
        {
            $translate = $this->get('translator');
            try
            {
                $directoryHandler->process();
            }
            catch (UniqueConstraintViolationException $exception)
            {
                $this->addFlash('warning', $translate->trans('directory.flash_messages.add_fail_already_existed'));
                return $this->redirect($request->getUri());
            }
            $add = $id ? $translate->trans('directory.flash_messages.update') : $translate->trans('directory.flash_messages.add');
            $this->addFlash('success', $add);
            if ($this->get('security.authorization_checker')->isGranted('ROLE_ADMIN'))
            {
                return $this->redirectToRoute('app.admin.directory.index');
            }
            else
            {
                return $this->redirectToRoute('/searchdirectory');
            }
        }
        //envoi du formulaire sous forme de vue
        return $this->render('admin/directory/form.html.twig', [
            'form'=>$form->createView(),
            'directory' => $entity
        ]);
    }
    /**
     * @Route("/admin/directory/delete/{id}", name="app.admin.directory.delete", requirements={"id" = "\d+"})
     */
    public function deleteAction(Request $request, $id)
    {
        //Service de gestion du formulaire
        $directoryHandler = $this->get('app.service.handler.directoryhandler');
        $directoryHandler->delete($id, 'AppBundle:Directory' );
//        $translate  = $this->get('translator');
//        $delete     = $translate->trans('form.directory.message.delete');
        $delete     = 'Film supprimé avec succès';
        $this->addFlash('success', $delete);
        return $this->redirectToRoute('app.main.search');
    }
}
