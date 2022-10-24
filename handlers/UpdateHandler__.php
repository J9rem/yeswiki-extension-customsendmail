<?php

/*
 * This file is part of the YesWiki Extension customsendmail.
 *
 * Authors : see README.md file that was distributed with this source code.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace YesWiki\Customsendmail;

use Configuration;
use Exception;
use Symfony\Component\Security\Csrf\Exception\TokenNotFoundException;
use YesWiki\Bazar\Service\EntryManager;
use YesWiki\Bazar\Service\FormManager;
use YesWiki\Bazar\Service\ListManager;
use YesWiki\Core\Controller\CsrfTokenController;
use YesWiki\Core\YesWikiHandler;
use YesWiki\Security\Controller\SecurityController;

class UpdateHandler__ extends YesWikiHandler
{
    public const FRENCH_DEPARTMENTS_TITLE = "Départements français";
    public const FRENCH_DEPARTMENTS_LIST_NAME = "ListeDepartementsFrancais";
    public const FRENCH_AREAS_TITLE = "Régions françaises";
    public const FRENCH_AREAS_LIST_NAME = "ListeRegionsFrancaises";

    protected $csrfTokenController;
    protected $entryManager;
    protected $formManager;
    protected $listManager;
    protected $securityController;

    public function run()
    {
        $this->csrfTokenController = $this->getService(CsrfTokenController::class);
        $this->entryManager = $this->getService(EntryManager::class);
        $this->formManager = $this->getService(FormManager::class);
        $this->securityController = $this->getService(SecurityController::class);
        $this->listManager = $this->getService(ListManager::class);
        if ($this->securityController->isWikiHibernated()) {
            throw new Exception(_t('WIKI_IN_HIBERNATION'));
        };
        if (!$this->wiki->UserIsAdmin()) {
            return null;
        }

        // add List if not existing
        if (!empty($_GET['appendCustomSendMailObject']) && 
            is_string($_GET['appendCustomSendMailObject'])){
            $output = '<strong>Extension customsendmail</strong><br/>';
            $output .= $this->addListIfNotExisting($_GET['appendCustomSendMailObject']);
            $output .= '<hr/>';
    
            // set output
            $this->output = str_replace(
                '<!-- end handler /update -->',
                $output.'<!-- end handler /update -->',
                $this->output
            );
        }
        return null;
    }
    
    private function addListIfNotExisting(string $appendCustomSendMailObject): string
    {
        try {
           $this->csrfTokenController->checkToken("customsendmail\\handler\\update__\\$appendCustomSendMailObject", 'GET', 'token');
        } catch (TokenNotFoundException $th) {
           $output = "&#10060; not possible to update an object : '{$th->getMessage()}' !<br/>";
           return $output;
        }
        switch ($appendCustomSendMailObject) {
            case 'departments':
                $output = "ℹ️ Updating list of departments... ";
                $list = $this->listManager->getOne(self::FRENCH_DEPARTMENTS_LIST_NAME);
                if (!empty($list)){
                    $output .= "&#10060; not possible to create list of departments : '".self::FRENCH_DEPARTMENTS_LIST_NAME."' is alreadyExisting !<br/>";
                    return $output;
                } elseif (!$this->createDepartements()){
                    $output = "&#10060; not possible to create list of departments : '".self::FRENCH_DEPARTMENTS_LIST_NAME."' error during creation !<br/>";
                    return $output;
                }
                break;
            case 'areas':
                $output = "ℹ️ Updating list of areas... ";
                $list = $this->listManager->getOne(self::FRENCH_AREAS_LIST_NAME);
                if (!empty($list)){
                    $output .= "&#10060; not possible to create list of areas : '".self::FRENCH_AREAS_LIST_NAME."' is alreadyExisting !<br/>";
                    return $output;
                } elseif (!$this->createAreas()) {
                    $output = "&#10060; not possible to create list of areas : '".self::FRENCH_AREAS_LIST_NAME."' error during creation !<br/>";
                    return $output;
                }
                break;
            case 'form':
                $output = "ℹ️ Updating form associating areas and departments... ";
                $formId = $this->params->get('formIdAreaToDepartment');
                if(!empty($formId) && (!is_scalar($formId) || strval($formId) != strval(intval($formId)) || intval($formId)<0)){
                    $output .= "&#10060; parameter 'formIdAreaToDepartment' is defined but with a bad format !<br/>";
                    return $output;
                }
                if (!empty($formId)){
                    $form = $this->formManager->getOne($formId);
                    if (!empty($form)){
                        $output .= "&#10060; impossible to create the form because already existing !<br/>";
                        return $output;
                    }
                }
                
                $listDept = $this->listManager->getOne(self::FRENCH_DEPARTMENTS_LIST_NAME);
                if (empty($listDept) && !$this->createDepartements()){
                    $output = "&#10060; not possible to create list of departments : '".self::FRENCH_DEPARTMENTS_LIST_NAME."' error during creation !<br/>";
                    return $output;
                }
                $listArea = $this->listManager->getOne(self::FRENCH_AREAS_LIST_NAME);
                if (empty($listArea) && !$this->createAreas()) {
                    $output = "&#10060; not possible to create list of areas : '".self::FRENCH_AREAS_LIST_NAME."' error during creation !<br/>";
                    return $output;
                }
                $deptListName = self::FRENCH_DEPARTMENTS_LIST_NAME;
                $arealistName = self::FRENCH_AREAS_LIST_NAME;
                if (empty($formId)){
                    $formId = $this->formManager->findNewId();
                }
                $form = $this->formManager->create([
                    'bn_id_nature' => $formId,
                    'bn_label_nature' => 'Correspondance régions - départements',
                    'bn_template' => 
                    <<<TXT
                    titre***Départements de {{bf_region}}***Titre Automatique***
                    liste***$arealistName***Région*** *** *** ***bf_region*** ***1*** *** *** * *** * *** *** *** ***
                    checkbox***$deptListName***Départements*** *** *** ***bf_departement*** ***1*** *** *** * *** * *** *** *** ***
                    acls*** * ***@admins***comments-closed***
                    TXT,
                    'bn_description' => '',
                    'bn_sem_context' => '',
                    'bn_sem_type' => '',
                    'bn_condition' => ''
                ]);
                $form = $this->formManager->getOne($formId);
                if (empty($form)){
                    $output = "&#10060; not possible to create the form : error during creation !<br/>";
                    return $output;
                } else {
                    $this->saveFormIdInConfig($formId);
                    $this->createEntriesForAssociation($formId);
                }
                break;
            
            default:
                $output = "&#10060; not possible to update an object : type '$appendCustomSendMailObject' is unknown !<br/>";
                return $output;
        }

        $output .= '✅ Done !<br />';

        return $output;
    }

    private function createDepartements(): bool
    {
        $this->listManager->create(self::FRENCH_DEPARTMENTS_TITLE,[
            "1"=> "Ain",
            "2"=> "Aisne",
            "3"=> "Allier",
            "4"=> "Alpes-de-Haute-Provence",
            "5"=> "Hautes-Alpes",
            "6"=> "Alpes-Maritimes",
            "7"=> "Ardèche",
            "8"=> "Ardennes",
            "9"=> "Ariège",
            "10"=> "Aube",
            "11"=> "Aude",
            "12"=> "Aveyron",
            "13"=> "Bouches-du-Rhône",
            "14"=> "Calvados",
            "15"=> "Cantal",
            "16"=> "Charente",
            "17"=> "Charente-Maritime",
            "18"=> "Cher",
            "19"=> "Corrèze",
            "2A"=> "Corse-du-Sud",
            "2B"=> "Haute-Corse",
            "21"=> "Côte-d'Or",
            "22"=> "Côtes-d'Armor",
            "23"=> "Creuse",
            "24"=> "Dordogne",
            "25"=> "Doubs",
            "26"=> "Drôme",
            "27"=> "Eure",
            "28"=> "Eure-et-Loir",
            "29"=> "Finistère",
            "30"=> "Gard",
            "31"=> "Haute-Garonne",
            "32"=> "Gers",
            "33"=> "Gironde",
            "34"=> "Hérault",
            "35"=> "Ille-et-Vilaine",
            "36"=> "Indre",
            "37"=> "Indre-et-Loire",
            "38"=> "Isère",
            "39"=> "Jura",
            "40"=> "Landes",
            "41"=> "Loir-et-Cher",
            "42"=> "Loire",
            "43"=> "Haute-Loire",
            "44"=> "Loire-Atlantique",
            "45"=> "Loiret",
            "46"=> "Lot",
            "47"=> "Lot-et-Garonne",
            "48"=> "Lozère",
            "49"=> "Maine-et-Loire",
            "50"=> "Manche",
            "51"=> "Marne",
            "52"=> "Haute-Marne",
            "53"=> "Mayenne",
            "54"=> "Meurthe-et-Moselle",
            "55"=> "Meuse",
            "56"=> "Morbihan",
            "57"=> "Moselle",
            "58"=> "Nièvre",
            "59"=> "Nord",
            "60"=> "Oise",
            "61"=> "Orne",
            "62"=> "Pas-de-Calais",
            "63"=> "Puy-de-Dôme",
            "64"=> "Pyrénnées-Atlantiques",
            "65"=> "Hautes-Pyrénnées",
            "66"=> "Pyrénnées-Orientales",
            "67"=> "Bas-Rhin",
            "68"=> "Haut-Rhin",
            "69"=> "Rhône",
            "70"=> "Haute-Saône",
            "71"=> "Saône-et-Loire",
            "72"=> "Sarthe",
            "73"=> "Savoie",
            "74"=> "Haute-Savoie",
            "75"=> "Paris",
            "76"=> "Seine-Maritime",
            "77"=> "Seine-et-Marne",
            "78"=> "Yvelines",
            "79"=> "Deux-Sèvres",
            "80"=> "Somme",
            "81"=> "Tarn",
            "82"=> "Tarn-et-Garonne",
            "83"=> "Var",
            "84"=> "Vaucluse",
            "85"=> "Vendée",
            "86"=> "Vienne",
            "87"=> "Haute-Vienne",
            "88"=> "Vosges",
            "89"=> "Yonne",
            "90"=> "Territoire-de-Belfort",
            "91"=> "Essonne",
            "92"=> "Hauts-de-Seine",
            "93"=> "Seine-Saint-Denis",
            "94"=> "Val-de-Marne",
            "95"=> "Val-d'Oise",
            "99"=> "Etranger",
            "971"=> "Guadeloupe",
            "972"=> "Martinique",
            "973"=> "Guyane",
            "974"=> "Réunion",
            "975"=> "St-Pierre-et-Miquelon",
            "976"=> "Mayotte",
            "977"=> "Saint-Barthélemy",
            "978"=> "Saint-Martin",
            "986"=> "Wallis-et-Futuna",
            "987"=> "Polynésie-Francaise",
            "988"=> "Nouvelle-Calédonie"
        ]);
        $list = $this->listManager->getOne(self::FRENCH_DEPARTMENTS_LIST_NAME);
        return !empty($list);
    }

    private function createAreas(): bool
    {
        // CODE ISO 3166-2
        $this->listManager->create(self::FRENCH_AREAS_TITLE,[
            "ARA"=> "Auvergne-Rhône-Alpes",
            "BFC"=> "Bourgogne-Franche-Comté",
            "BRE"=> "Bretagne",
            "CVL"=> "Centre-Val de Loire",
            "COR"=> "Corse",
            "GES"=> "Grand Est",
            "HDF"=> "Hauts-de-France",
            "IDF"=> "Île-de-France",
            "NOR"=> "Normandie",
            "NAQ"=> "Nouvelle-Aquitaine",
            "OCC"=> "Occitanie",
            "PDL"=> "Pays de la Loire",
            "PAC"=> "Provence-Alpes-Côte d'Azur",
            "GUA"=> "Guadeloupe",
            "GUF"=> "Guyane",
            "LRE"=> "La Réunion",
            "MTQ"=> "Martinique",
            "MAY"=> "Mayotte",
            "COM"=> "Collectivités d'outre-mer",
        ]);
        $list = $this->listManager->getOne(self::FRENCH_AREAS_LIST_NAME);
        return !empty($list);
    }

    private function saveFormIdInConfig($formId)
    {
        // default acls in wakka.config.php
        include_once 'tools/templates/libs/Configuration.php';
        $config = new Configuration('wakka.config.php');
        $config->load();

        $baseKey = 'formIdAreaToDepartment';
        $config->$baseKey = $formId;
        $config->write();
        unset($config);
    }

    private function createEntriesForAssociation($formId)
    {
        foreach ([
            'ARA' => "1,3,7,15,26,38,42,43,63,69,73,74",
            'BFC' => "21,25,39,58,70,71,89,90",
            'BRE' => "22,29,35,44,56",
            "CVL" => "18,28,36,37,41,45",
            "COR" => "2A,2B",
            "GES" => "8,10,51,52,54,55,57,67,68,88",
            "HDF" => "2,59,60,62,80",
            "IDF" => "75,77,78,91,92,93,94,95",
            "NOR" => "14,27,50,61,76",
            "NAQ" => "16,17,19,23,24,33,40,47,64,79,86,87",
            "OCC" => "9,11,12,30,31,32,34,46,48,65,66,81,82",
            "PDL" => "44,49,53,72,85",
            "PAC" => "4,5,6,13,83,84",
            "GUA" => "971",
            "GUF" => "973",
            "LRE" => "974",
            "MTQ" => "972",
            "MAY" => "976",
            "COM" => "975,977,978,986,987",
        ] as $areaCode => $depts) {
            $this->entryManager->create(
                $formId,
                [
                    'antispam' => 1,
                    'bf_titre' => "Départements de {{bf_region}}",
                    'liste'.self::FRENCH_AREAS_LIST_NAME.'bf_region' => $areaCode,
                    'checkbox'.self::FRENCH_DEPARTMENTS_LIST_NAME.'bf_departement' => $depts,
                ],
            );
        }
    }
}
