<?php
/**
 * Created by PhpStorm.
 * User: wamobi10
 * Date: 19/12/16
 * Time: 12:51
 */

namespace AppBundle\Service\Twig;


class FunctionExtension extends \Twig_Extension
{
    private $twig;

    public function __construct(\Twig_Environment $twig){
        $this->twig = $twig;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('renderTree',[$this,'renderTree'])
        ];
    }


    public function renderTree($directories = null,$parent = 1, $limit = 1){

        if(is_null($directories))
            return "";

        $tree = $this->_generateTreeMenu($directories,$parent,$limit);
        return $tree;
    }

    protected function _generateTreeMenu($directories, $parent = 1, $limit = 0)
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