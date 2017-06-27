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
            new \Twig_SimpleFunction(
                'renderTree',
                [$this,'renderTree'],
                array('is_safe' => array('html') )
                )
        ];
    }


    public function renderTree($directories = null,$parent = 1, $limit = 1){

        if(is_null($directories))
            return "";

        return '<ul class="jstree-container-ul jstree-children" role="group"><li role="treeitem" class="directory" data-id="2"><a>2017</a><ul class="jstree-container-ul jstree-children" role="group"><li role="treeitem" class="directory" data-id="3"><a>03</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li><li role="treeitem" class="directory" data-id="4"><a>04</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li><li role="treeitem" class="directory" data-id="5"><a>06</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li></ul></li><li role="treeitem" class="directory" data-id="7"><a>Html</a><ul class="jstree-container-ul jstree-children" role="group"><li role="treeitem" class="directory" data-id="8"><a>Laius_PREVINTER</a><ul class="jstree-container-ul jstree-children" role="group"><li role="treeitem" class="directory" data-id="9"><a>Laius PREVINTER_fichiers</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li></ul></li><li role="treeitem" class="directory" data-id="10"><a>presentation vivinter RH  PRC_fichiers</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li></ul></li><li role="treeitem" class="directory" data-id="23"><a>flv</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li><li role="treeitem" class="directory" data-id="24"><a>ics</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li><li role="treeitem" class="directory" data-id="25"><a>jpg</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li><li role="treeitem" class="directory" data-id="26"><a>mp4</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li><li role="treeitem" class="directory" data-id="27"><a>pdf</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li><li role="treeitem" class="directory" data-id="28"><a>pps</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li><li role="treeitem" class="directory" data-id="29"><a>ppt</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li><li role="treeitem" class="directory" data-id="30"><a>swf</a><ul class="jstree-container-ul jstree-children" role="group"></ul></li></ul>';
        $tree = $this->_generateTreeMenu($directories,$parent,$limit);
        return $tree;
    }

    protected function _generateTreeMenu($directories, $parent = 1, $limit = 0)
    {
        if ($limit > 1000) return '';
        $tree = '';
        $tree = '<ul class="jstree-container-ul jstree-children" role="group">';

        for ($i = 0, $ni = count($directories); $i < $ni; $i++) {
            if ($directories[$i]->getParent() && $directories[$i]->getParent()->getId() == $parent) {
                $tree .= '<li role=\'treeitem\' class=\'directory\' data-id=\'' . $directories[$i]->getId() . '\'><a>';
                $tree .= $directories[$i]->getName() . '</a>';
                $tree .= $this->_generateTreeMenu($directories, $directories[$i]->getId(), $limit++);
                $tree .= '</li>';
            }
        }
        $tree .= '</ul>';
        return $tree;
    }

}