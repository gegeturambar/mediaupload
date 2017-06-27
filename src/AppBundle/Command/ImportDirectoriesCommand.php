<?php

namespace AppBundle\Command;


use AppBundle\Entity\Directory;
use AppBundle\Repository\DirectoryRepository;
use AppBundle\Repository\MovieRepository;
use AppBundle\Repository\RoleRepository;
use AppBundle\Service\Repository\ExtensionRepository;
use AppBundle\Service\Utils\SlugUtils;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\VarDumper\Caster\ReflectionCaster;


class ImportDirectoriesCommand extends ContainerAwareCommand
{

    /* @var Registry $doctrine */
    protected $doctrine;

    /* @var SlugUtils $slugify */
    protected $slugify;

    protected function configure()
    {
        $this
            ->setName('app:directories:import')
            ->setDescription('import directories and copy them')
            ->setHelp('this command allow you to import your directories')
            ->addArgument('pathSrc', InputArgument::OPTIONAL,'path from which we copy directories')
            ->addArgument('pathDest', InputArgument::OPTIONAL,'path where we copy the directories')
        ;

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path_source = (integer)$input->getArgument('pathSrc');
        $path_source = $path_source ? $path_source : $this->getContainer()->getParameter('defaultPathSrc');

        $path_dest = (integer)$input->getArgument('pathDest');
        $path_dest = $path_dest ? $path_dest : $this->getContainer()->getParameter('defaultPathDest');

        /** @var ExtensionRepository $extensionRepository */
        $extensionRepository = $this->getContainer()->get('app.services.repository.extension');
        $extensionRepository->initExtensions();


        $this->slugify = $this->getContainer()->get('app.service.utils.slug');


        $this->doctrine = $this->getContainer()->get('doctrine');
        $em = $this->doctrine->getManager();
        /* @var \Doctrine\DBAL\Connection $connection */
        $connection = $this->doctrine->getConnection();


        /* @var RoleRepository $roleModel */
        $roleModel = $this->doctrine->getRepository('AppBundle:Role');
        $role = $roleModel->findOneBy(array('name'=>'ROLE_SUPERUSER'));

        /* @var DirectoryRepository $directoryModel */
        $directoryModel = $this->doctrine->getRepository('AppBundle:Directory');
        $ret = $directoryModel->init($path_dest,$role);

        $dir = $directoryModel->fetchOneByNameAndParent("ROOT");

        $this->copyDirectory('upload', $path_source, $path_dest, $dir->getId());

        $output->writeln("You have copied all directories and create the records in the database accordingly" );
    }

    function copyDirectory($item, $path_source, $path_dest,$parent_id = 1)
    {
        if (!is_dir($path_source))
            return;

        $items = scandir($path_source);

        /* @var DirectoryRepository $directoryModel */
        $directoryModel = $this->doctrine->getRepository('AppBundle:Directory');
        /* @var RoleRepository $roleModel */
        $roleModel = $this->doctrine->getRepository('AppBundle:Role');

        $corresp = array(
            "exe"	=>  "application/octet-stream",
            "html"  =>  "text/html",
            "flv"   =>  "video/flv",
            "jpg"   =>  "image/jpeg",
            "jpeg"  =>  "image/jpeg",
            "mp3"   =>  "audio/mpeg3",
            "mp4"   =>  "audio/mpeg4",
            "pdf"   =>  "application/pdf",
            "pps"   =>  "application/mspowerpoint",
            "ppt"   =>  "application/mspowerpoint",
            "swf"   =>  "application/x-shockwave-flash"
        );

        $bug = false;
        foreach ($items as $item) {
            if($item == '.' || $item == '..')
                continue;
            $goodName = $this->slugify->generateSlug($item);
            $path = $path_source . "\\$item";
            if (is_dir($path)) {
                //create directory
                $newPath = $path_dest.DIRECTORY_SEPARATOR.$goodName;
                if(!file_exists($newPath)) {
                    mkdir($newPath, 0777, true);
                }

                $parent = $directoryModel->find($parent_id);
                // add directory to bdd if not exists

                $directory = $directoryModel->fetchOneByNameAndParent($goodName, $parent);

                if(!$directory){
                    //create
                    $directory = new Directory();
                    $directory->setParent($parent);
                    $directory->setPath($newPath);
                    $directory->setName($goodName);

                    if($parent_id == 1) {
                        // get
                        $access = in_array(strtolower($goodName), array("exe", "lettre22", "lettre23")) ? "ADMIN" : "USER";
                        $access = $roleModel->findOneBy(array('name'=>$access));
                        $directory->setAccess($access);
                        /*
                         *
                        $exts = Utils::getConf()['import']['extensions'];
                        $extensions = array();
                        foreach ($corresp as $key => $val) {
                            if (strpos(strtolower($item), $key) !== false) {
                                $extensions[] = $val;
                            }
                        }
                        $extensions = implode(",",$extensions);
                        */
                    }else{
                        // take from parentid
                        $dad = $directoryModel->find($parent_id);
                        $directory->setAccess( $dad->getAccess() );
                    }


                    $manager = $this->doctrine->getManager();
                    try{
                        $manager->persist($directory);
                        $manager->flush();
                    }catch (DriverException $ex){
                        echo $ex->getMessage();
                        var_dump($goodName);
                        var_dump($items);die();
                        $bug = true;
                        continue;
                    }

                    //$newParentId = $directoryModel->save(array('name'=>$item,'parentid'=>$parent_id, "path" => $path_dest . DIRECTORY_SEPARATOR . $item,"access"=>$access));
                }

                $newParentId = $directory->getId();

                $this->copyDirectory($goodName, $path, $path_dest . "\\$goodName", $newParentId);

            }
        }
    }
}