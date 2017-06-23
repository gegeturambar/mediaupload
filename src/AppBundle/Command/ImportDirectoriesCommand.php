<?php

namespace AppBundle\Command;


use AppBundle\Repository\DirectoryRepository;
use AppBundle\Repository\MovieRepository;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class ImportDirectoriesCommand extends ContainerAwareCommand
{

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

        $doctrine = $this->getContainer()->get('doctrine');
        $em = $doctrine->getManager();

        /* @var DirectoryRepository $rc */
        $rc = $doctrine->getRepository('AppBundle:Directory');
        $rc->init();
        $count = $rc->updateDirectoriesPrice($discount_rate);

        $directoryModel   = new Directories();
        $ret = $directoryModel->init();

        if($ret) {
            $dir = $directoryModel->fetchOneByNameAndParent("ROOT");
            copyDirectory('upload', $path_source, $path_dest ,$dir->id);
        }

        $output->writeln("You have copy all directories and create the records in the database accordingly" );
    }

    function copyDirectory($item, $path_source, $path_dest,$parent_id = 1)
    {
        if (!is_dir($path_source))
            return;

        $items = scandir($path_source);

        $directoryModel   = new Directories();

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

        foreach ($items as $item) {
            if($item == '.' || $item == '..')
                continue;
            $path = $path_source . "\\$item";

            if (is_dir($path)) {
                //create directory
                if(!file_exists($path_dest.DIRECTORY_SEPARATOR.$item)) {
                    mkdir($path_dest . DIRECTORY_SEPARATOR . $item);
                }


                // add directory to bdd if not exists
                $directory = $directoryModel->fetchOneByNameAndParent($item, $parent_id);
                if(!$directory){
                    //create

                    if($parent_id == 1) {
                        $access = in_array(strtolower($item), array("exe", "lettre22", "lettre23")) ? "ADMIN" : "USER";
                        $exts = Utils::getConf()['import']['extensions'];
                        $extensions = array();
                        foreach ($corresp as $key => $val) {
                            if (strpos(strtolower($item), $key) !== false) {
                                $extensions[] = $val;
                            }
                        }
                        $extensions = implode(",",$extensions);
                    }else{
                        // take from parentid
                        $dad = $directoryModel->fetchOne($parent_id);
                        $extensions = $dad->extensions;
                        $access = $dad->access;
                    }
                    $newParentId = $directoryModel->save(array('name'=>$item,'parentid'=>$parent_id, "path" => $path_dest . DIRECTORY_SEPARATOR . $item,"access"=>$access,"extensions"=>$extensions));
                }else{
                    $newParentId = $directory->id;
                }

                copyDirectory( $item, $path, $path_dest."\\$item", $newParentId);
            }
        }
    }
}