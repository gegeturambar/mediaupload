<?php
/**
 * Created by PhpStorm.
 * User: wamobi5
 * Date: 22/12/16
 * Time: 11:03
 */

namespace AppBundle\Service\Utils;


use AppBundle\Repository\DirectoryRepository;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadUtils
{

    private $stringUtils;
    private $pathDest;
    private $maxSize;
    private $fileformat;
    private $dateformat;

    /* @var Registry $doctrine */
    private $doctrine;



    public function __construct(StringUtils $stringUtils, $pathDest, $import, $doctrine)
    {
        $this->stringUtils = $stringUtils;
        $this->pathDest = $pathDest;
        $this->maxSize = $import['maxsize'];
        $this->fileformat = $import['fileformat'];
        $this->dateformat = $import['dateformat'];
        $this->doctrine = $doctrine;
    }

    public function simpleuploadFunction(UploadedFile $file, $path = null)
    {

        return move_uploaded_file($file['tmp_name'][$i], $destination);

        $rename = $this->stringUtils->generateUniqString(32);
        $extension = $file->guessExtension() === 'jpeg' ? 'jpg' : $file->guessExtension();
        $file->move($this->pathDest.$path, $rename.'.'.$extension);

        return $rename.'.'.$extension;

        /*

         */
    }

    public function uploadMulti($files, $maxsize=FALSE){

        $maxsize = $maxsize ? $this->maxsize : $maxsize;
        $i = 0;

        /* @var DirectoryRepository $rep */
        $rc = $this->doctrine->getRepository("AppBundle:Directory");

        for($count = count($files['name']),$i=0;$i<$count;$i++){
            $options = array();
           foreach(array('name','type','tmp_name','error','size') as $attr){
               $options[$attr] = $files[$attr][$i];
           }
           $options["directory"] = $rc->find($files['directories'][$i]);

           if ($this->verifUpload($options,$maxsize)) {
               $this->uploadFunction($options);
           }
        }
    }

    protected function checkExtension($extension, $directory){
        $extensions = $directory->getExtensions();
        if(count($extensions) <= 0){
            $rc = $this->doctrine->getRepository("AppBundle:Extension");
            $extensions = $rc->findBy(array('basic'=>true));
        }
        return in_array($extension,$extensions);
    }

    public function verifUpload($file,$maxsize){
        // $index, $i, $maxsize=FALSE,$extensions=FALSE){
        //Test1: fichier correctement uploadé
        if ( $file['error'] > 0) return FALSE;
        //Test2: taille limite
        if ($maxsize !== FALSE AND $file['size'] > $maxsize) return FALSE;

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $ext = finfo_file($finfo, $file['tmp_name']);

        //get all extensions by directory
        return $this->checkExtension($ext,$file['directory']);
    }
}