<?php
/**
 * Created by PhpStorm.
 * User: wamobi5
 * Date: 22/12/16
 * Time: 11:02
 */
namespace AppBundle\Service\Utils;
class StringUtils
{
    public function generateUniqString($length){
        return bin2hex(openssl_random_pseudo_bytes($length / 2));
    }
}