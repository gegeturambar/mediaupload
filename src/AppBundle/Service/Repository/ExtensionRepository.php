<?php
/**
 * Created by PhpStorm.
 * User: wamobi5
 * Date: 19/12/16
 * Time: 12:50
 */

namespace AppBundle\Service\Repository;


use AppBundle\Entity\Extension;
use Doctrine\Bundle\DoctrineBundle\Registry;

class ExtensionRepository
{
    protected $doctrine;

    public function __construct(Registry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    function initExtensions(){

        $em = $this->doctrine->getEntityManager();
        $qb = $em->createQueryBuilder();
        $qb->select("count(extension.id)");
        $qb->from("AppBundle:Extension","extension");
        $count = $qb->getQuery()->getSingleScalarResult();

        if($count <= 0) {
            // create all starting extensions
            $corresp = array(
                "exe" => "application/octet-stream",
                "html" => "text/html",
                "flv" => "video/flv",
                "jpg" => "image/jpeg",
                "jpeg" => "image/jpeg",
                "mp3" => "audio/mpeg3",
                "mp4" => "audio/mpeg4",
                "pdf" => "application/pdf",
                "pps" => "application/mspowerpoint",
                "ppt" => "application/mspowerpoint",
                "swf" => "application/x-shockwave-flash"
            );

            foreach ($corresp as $key => $item) {
                $extension = new Extension();
                $extension->setExt($key);
                $extension->setMimeType($item);
                $em->persist($extension);
                $em->flush();
            }
        }
    }

}