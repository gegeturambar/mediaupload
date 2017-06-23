<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $directories = $em->getRepository("AppBundle:Directory")->findAll();

        // replace this example code with whatever you need
        return $this->render('default/index.html.twig', [
            'base_dir' => realpath($this->getParameter('kernel.project_dir')) . DIRECTORY_SEPARATOR,
            'directories' => $directories
        ]);
    }

    //TODO create twig function
    function generateTreeMenu($directories, $parent = 1, $limit = 0)
    {
        if ($limit > 1000) return '';
        $tree = '';
        $tree = '<ul class="jstree-container-ul jstree-children" role="group">';

        for ($i = 0, $ni = count($directories); $i < $ni; $i++) {
            if ($directories[$i]->parentid == $parent) {
                $tree .= '<li role=\'treeitem\' class=\'directory\' data-id=\'' . $directories[$i]->id . '\'><a>';
                $tree .= $directories[$i]->name . '</a>';
                $tree .= generateTreeMenu($directories, $directories[$i]->id, $limit++);
                $tree .= '</li>';
            }
        }
        $tree .= '</ul>';
        return $tree;
    }

}
