<?php
/**
 * Created by PhpStorm.
 * User: wamobi10
 * Date: 21/12/16
 * Time: 12:16
 */

namespace AppBundle\Service\Handler;


use AppBundle\Entity\Role;
use AppBundle\Service\Utils\StringUtils;
use AppBundle\Service\Utils\UploadUtils;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\EventDispatcher\Tests\Service;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

class UserHandler extends FormHandler
{

    private $encoder;
    public function __construct(Registry $doctrine,UserPasswordEncoder $encoder){
        $this->doctrine = $doctrine;
        $this->encoder = $encoder;
    }

    public function process(){
        $data = $this->form->getData();
        $data->setPassword($this->encoder->encodePassword($data, $data->getPassword() ));
        $data = $this->form->getData();

        $rc = $this->doctrine->getRepository("AppBundle:Role");
        $role = $rc->getDefaultRole();
        $data->setRoles($role);

        return parent::process();
    }
}