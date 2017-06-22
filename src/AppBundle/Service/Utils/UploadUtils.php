<?php
/**
 * Created by PhpStorm.
 * User: wamobi5
 * Date: 22/12/16
 * Time: 11:03
 */

namespace AppBundle\Service\Utils;


use Symfony\Component\HttpFoundation\File\UploadedFile;

class UploadUtils
{

    private $stringUtils;
    private $dir;

    public function __construct(StringUtils $stringUtils, $dir)
    {
        $this->stringUtils = $stringUtils;
        $this->dir = $dir;
    }

    public function uploadFunction(UploadedFile $file, $folder)
    {
        $rename = $this->stringUtils->generateUniqString(32);
        $extension = $file->guessExtension() === 'jpeg' ? 'jpg' : $file->guessExtension();
        $file->move($this->dir.$folder, $rename.'.'.$extension);


        return $rename.'.'.$extension;

        /*

         */
    }
}