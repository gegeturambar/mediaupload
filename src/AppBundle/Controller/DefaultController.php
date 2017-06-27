<?php

namespace AppBundle\Controller;

use AppBundle\Repository\DirectoryRepository;
use AppBundle\Service\Utils\SlugUtils;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationChecker;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="app.homepage.index")
     */
    public function indexAction(Request $request)
    {
        /* @var AuthorizationChecker $securityContext */
        $securityContext = $this->container->get('security.authorization_checker');
        if (!$securityContext->isGranted('IS_AUTHENTICATED_FULLY')) {
            $response = $this->forward('AppBundle:Security:login');
            return $response;
        }




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

    /**
     * @Route("/upload", name="app.homepage.upload")
     */
    public function envoiformulaireAction(Request $request, $message = null, $files = null, $directories = null){

        $message = $request->isXmlHttpRequest() ? $request->request->get('message') : $message;
        $directories_id = $request->isXmlHttpRequest() ? $request->request->get('directories') : $directories;
        $files = $_FILES;

        $extensions_base = ['pdf', 'jpg', 'jpeg', 'png', 'image/jpeg', 'image/png', 'application/pdf'];
        // now extensions depends on directory

        // foreach files check mimes

        $count = count($_FILES['myfile']['name']);

        $retour = [];
        $error ='';
        $name_files ='';
        $files =[];
        $donneeFile =[];
        $realCount = 0;

        /*
        for($i =0;$i<$count; $i++){
            if($_FILES['myfile']['name'][$i]==''){
                $realCount=$realCount-1;
                continue;
            }
            $verif = Utils::verifUploadMulti('myfile', $i, FALSE, $extensions_base);
            if($verif!=true)
                $error .= sprintf(ERROR_UPLOAD_FORM_1, $_FILES['myfile']['name'][$i]).'<br/>';
        }
*/
        $realCount = 0;
        if($error=='') {

            // mk upload
            $path_dest = '../../upload';



            $datetime = new \DateTime();
            $import = $this->getParameter("import");

            $doctrine = $this->getDoctrine();
            // Pour select

            /* @var DirectoryRepository $dirRep */
            $dirRep = $doctrine->getRepository("AppBundle:Directory");

            if(date('H') > $import['hourjob']) {
                $datetime->modify('+1 day');
            }
            $date = $datetime->format('Y-m-d');
            $msgRet = "<span>Les fichiers seront accessibles sur les urls suivantes le $date => </span><ul>";
            for ($i = 0; $i < $count; $i++) {

                if ($_FILES['myfile']['name'][$i] == '') continue;

                // get directory
                $titleImg = $_FILES['myfile']['name'][$i];

                $pattern = '/^[a-zA-Z]+\.[a-zA-Z]+$/';
                if(!preg_match($pattern,$titleImg)){
                    $error .= sprintf(ERROR_UPLOAD_FORM_PATTERN, $titleImg).'<br/>';
                }
                $extension_upload = strtolower(substr(strrchr($titleImg, '.'), 1));

                /* @var SlugUtils $slugify */
                $slugify = $this->get('app.service.utils.slug');
                $titleImg_only = $slugify->generateSlug(substr($titleImg,0,strrpos($titleImg,'.')));

                $format = $import['fileformat'];
                $titleImg_only .= '_'.date($format);
                $titleImg = $titleImg_only . '.'.$extension_upload;
                $dir_id = $directories_id[$i];
                $directory = $dirRep->find($dir_id);

                $name_files      .= '<p>'.NOM_FORM.' : '.$titleImg.'<br/>'.TAILLE_FORM.' : '.($_FILES['myfile']['size'][$i]/1000).' ko</p>';

                $path_dest = $directory->path.DIRECTORY_SEPARATOR.$titleImg;
                $j = 0;
                while(file_exists($path_dest)){
                    $j++;
                    $path_dest = $directory->path.DIRECTORY_SEPARATOR.$titleImg_only.'_'.$j.$extension_upload;
                }

                $donneeFile['name']   = $_FILES['myfile']['name'][$i];
                $donneeFile['size']   = $_FILES['myfile']['size'][$i];
                $donneeFile['type']   = $_FILES['myfile']['type'][$i];
                $donneeFile['path']   = $path_dest;

                $files[] = $donneeFile;

                // here check extensions by directory
                $extensions = $directory->extensions ? explode(',', $directory->extensions) : $extensions_base;

                $upload = Utils::uploadMulti('myfile', $i, $path_dest, FALSE, $extensions);
                if(!$upload){
                    $error .= sprintf(ERROR_UPLOAD_FORM_1, $titleImg).'<br/>';
                }else{
                    // save records demande
                    $demandeModel = new Demandes();
                    $size = $_FILES['myfile']['size'][$i];
                    $type = $_FILES['myfile']['type'][$i];
                    $data = array('path'=>$path_dest,'size'=>$size,'type'=> $type,'userid'=> Utils::getCurrentUser()->id,'timestamp'=>date(Utils::getConf()['import']['dateformat']));
                    $ret = $demandeModel->save($data);
                    $msgRet .= $ret ? "<li>$path_dest</li>" : "";
                    $realCount = $ret ? ( $realCount + 1 ) :  $realCount ;
                }
            }
            $msgRet .= "</ul>";
        }

        $retour[] =$error;
        $retour[] =$name_files;


        //$count = $count +$realCount;
        //$retour[] =   sprintf(NOMBRE_FORM_UPLOAD, $count);
        if($realCount>0) {
            $date =
            $text =  "";
            $retour[] = $msgRet;
        }
        else
            $retour[] = AUCUN_FORM_UPLOAD;

        echo json_encode($retour);

        if($error==''){
            //insert t_histo_demandes
            /*
                        $objet      = 'Demande Formulaire Upload > '.$famille->MCHC_LIBELLE.' > '.$motif->MCHC_LIBELLE;

                       $demande    = Utils::messageBody($nom, $prenom, $genre, $naissance, $secu, $employeur, $email_bdd, $tel, $famille->MCHC_LIBELLE, $motif->MCHC_LIBELLE, $email, $message, $files);

                        $code_email = $motif->MCHC_ID+5100;

                        $HistoModel = new Histodemandes();


                        $data =[
                            'HDE_DATE_INSERTION'=> date("Y-m-d H:i:s"),
                            'HDE_ID_COMMUNICATION_QUALNET'=> 0,
                            'HDE_REF_COMMUNICATION_QUALNET'=> "",
                            'HDE_DATE_COMMUNICATION_RECUE'=> date("Y-m-d H:i:s"),
                            'HDE_DATE_COMMUNICATION_SAISIE'=> date("Y-m-d H:i:s"),
                            'HDE_ID_DEMANDE_QUALNET'=> '',
                            'HDE_REF_DEMANDE_QUALNET'=> '',
                            'HDE_TYPE_CLIENT'=> 'ASSURE',
                            'HDE_REF_CLIENT'=> $secu,
                            'HDE_NOMAPPEL'=> "",
                            'HDE_MEDIA' => 'Formulaire web hors connexion',
                            'HDE_CODE_TRAITEMENT'=> 1,
                            'HDE_CODE_DEMANDE'=> $code_email,
                            'HDE_LIBELLE_DEMANDE'=> $objet,
                            'HDE_TYPE_DEMANDE' => 'Demande',
                            'HDE_DATE_DEMANDE_RECUE' => date("Y-m-d H:i:s"),
                            'HDE_DATE_DEMANDE_SAISIE' => date("Y-m-d H:i:s"),
                            'HDE_DATE_DEMANDE_TRAITEE' => date("Y-m-d H:i:s"),
                            'HDE_DUREE_TRAITEMENT_ESTIMEE' => "",
                            'HDE_DEMANDE_EXPRIMEE' => $demande,
                            'HDE_LIB_STATUT' => 'En cours de traitement',
                            'HDE_AFFICHAGE_DDE_SUR_SITE' => 0,
                            'HDE_AFFICHAGE_DELAI_SUR_SITE' => 0,
                            'HDE_DATE_EXTRACT_VERS_QUALNET' => '',
                            'HDE_CIV_RH' => '',
                            'HDE_NOM_RH' => '',
                            'HDE_TEL_RH' => '',
                            'HDE_MAIL_RH' => '',
                            'HDE_TRANSFERT_WF' => 2,
                            'HDE_MARQUE' => addslashes($marque),
                            'HDE_BASE_SOURCE' => 'ASSPER'

                        ];
                        $idDemandeNew = $HistoModel->save($data);

                        $HistoModel->save(['HDE_ID'=>$idDemandeNew, 'HDE_ID_COMMUNICATION_QUALNET'=> -$idDemandeNew]);

                        //insert into t_fichiers_upload
                        if(count($files)>0) {
                            $uploadModel = new Histofichiers();
                            foreach($files as $ff) {
                                $data = [
                                    'FI_ID_DEMANDE' => $idDemandeNew,
                                    'FI_DATE_INSERTION' => date("Y-m-d H:i:s"),
                                    'FI_NOM_FICHIER_INIT' => addslashes($ff['name']),
                                    'FI_NOM_FICHIER_FINAL' => 'upload/' . $year . '/' . $month.'/'.$ff['newName'],
                                    'FI_TYPE_MIME' => addslashes($ff['type']),
                                    'FI_TAILLE' => $ff['size'],
                                    'FI_CHEMIN_FICHIER' => addslashes($ff['path']),
                                    'FI_DATE_EXTRACT_VERS_QUALNET' => ''
                                ];
                                $uploadModel->save($data);
                            }
                        }

                        //insert t_histo_email
                        $body = Utils::emailBody($idDemandeNew, $nom, $prenom, $genre, $naissance, $secu, $employeur, $email_bdd, $tel, $famille->MCHC_LIBELLE, $motif->MCHC_LIBELLE, $email, $message);


                        $objet      = DEMANDE_SUBJECT.' > '.$famille->MCHC_LIBELLE.' > '.$motif->MCHC_LIBELLE;

                        $code_email = $motif->MCHC_ID+5100;

                        $histoMailModel = new Histomail();
            // echo '<pre>';print_r($motif);die();

                        //a revoir : hem_marque
                        $data =[
                            'HEM_TYPE_DESTINATAIRE' => 'Gestion',
                            'HEM_REF_DESTINATAIRE' => '',
                            'HEM_EXPEDITEUR' => addslashes($email),
                            'HEM_DESTINATAIRE'=> $motif->MCHC_EMAIL_DESTINATAIRE,
                            'HEM_DESTINATAIRE_COPIE'=> '',
                            'HEM_CODE_EMAIL'=> $code_email,
                            'HEM_CODE_TRAITEMENT'=> 1,
                            'HEM_OBJET'=> $objet,
                            'HEM_CORPS'=> $body,
                            'HEM_FICHIER'=> '',
                            'HEM_DATE_INSERTION'=> date("Y-m-d H:i:s"),
                            'HEM_INFO'=> '',
                            'HEM_ENVOYE'=> null,
                            'HEM_DATE_TRAITEMENT'=> null,
                            'HEM_MARQUE'=> addslashes($marque),
                            'HEM_BASE_SOURCE'=> 'ASSPER',
                            'HEM_ID_COMMUNICATION_QUALNET'=> -$idDemandeNew,
                            'HEM_DEMANDE_ID'=> $idDemandeNew,
                            'HEM_AFFICHAGE_SITE'=> 0,
                            'HEM_TYPE_MAIL'=> 'email',
                            'HEM_NATURE_MAIL'=> 'Demande Gestion',
                            'HEM_ID_MAIL_QUALNET'=> '',
                            'HEM_DATE_EXTRACT_VERS_QUALNET'=> '',
                            'HEM_TYPE_ENVOI'=> 0,
                            'HEM_DESTINATAIRE_COPIE_CACHEE'=> ''

                        ];

                        // echo '<pre>';print_r($data);die();

                        $idMailNew = $histoMailModel->save($data);

                        // echo $body;die();
                        if($email != $email_bdd){

                            $message = Utils::emailSecurityBody($nom, $prenom, $genre, $email);

                            $data =[
                                'HEM_TYPE_DESTINATAIRE' => 'Assuré',
                                'HEM_REF_DESTINATAIRE' => $secu,
                                'HEM_EXPEDITEUR' => 'no-reply@vivinter.fr',
                                'HEM_DESTINATAIRE'=> $email_bdd,
                                'HEM_DESTINATAIRE_COPIE'=> '',
                                'HEM_CODE_EMAIL'=> 5500,
                                'HEM_CODE_TRAITEMENT'=> 1,
                                'HEM_OBJET'=> DEMANDE_SUBJECT2,
                                'HEM_CORPS'=> $message,
                                'HEM_FICHIER'=> '',
                                'HEM_DATE_INSERTION'=> date("Y-m-d H:i:s"),
                                'HEM_INFO'=> '',
                                'HEM_ENVOYE'=> null,
                                'HEM_DATE_TRAITEMENT'=> null,
                                'HEM_MARQUE'=> addslashes($marque),
                                'HEM_BASE_SOURCE'=> 'ASSPER',
                                'HEM_ID_COMMUNICATION_QUALNET'=> '',
                                'HEM_DEMANDE_ID'=> '',
                                'HEM_AFFICHAGE_SITE'=> 0,
                                'HEM_TYPE_MAIL'=> 'email',
                                'HEM_NATURE_MAIL'=> 'Alerte de sécurité',
                                'HEM_ID_MAIL_QUALNET'=> '',
                                'HEM_DATE_EXTRACT_VERS_QUALNET'=> '',
                                'HEM_TYPE_ENVOI'=> 0,
                                'HEM_DESTINATAIRE_COPIE_CACHEE'=> ''
                            ];
                        // echo '<pre>';print_r($data);die();
                            $histoMailModel->save($data);
                        }
                        */
        }

        die();
    }

}
