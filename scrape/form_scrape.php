<?php session_start();
require_once("./nomade/include/parametros.php");
require_once($CONFIG["NOMADEDOCROOT"] ."include/portlet.php");
require_once($CONFIG["NOMADEDOCROOT"] ."include/requestVars.php");
require_once($CONFIG["NOMADEDOCROOT"] ."include/util.php");
require_once($CONFIG["NOMADEDOCROOT"] ."framework/php/MySQLLib.php");
require_once($CONFIG["SMARTY_DIR"]    ."Smarty.class.php");
require_once($CONFIG["SITE_PAGEROOT"] . "./phputil/funcoesGUI.php");
require_once($_SERVER['DOCUMENT_ROOT']. '/config.php');


if (!isset($_SESSION['scrape'])) {
    //header('Location: '.BASEURL);
}

if (array_key_exists("map", $_POST) || array_key_exists("map", $_GET)) {

    $map = $_POST["map"];
    $region = $_POST["region"];
    $pagesPerFile = $_POST["pagesPerFile"];
    $fileType = $_POST["fileType"];
    $single = (isset($_POST["single"]) ? $_POST["single"] : '');
    $debug = (isset($_POST["debug"]) ? $_POST["debug"] : false);



    switch($map) {
        case 'Noticias':
        case 'Artigos':
        case 'Dicas':
            require_once(BASEPATH ."/novosite/nomade/framework/php/scrapeCore.php");
            $scrape = new ScrapeCore($pagesPerFile, $db, $region, $map, $fileType, $debug);
            break;
        case 'Gabaritos':
        case 'Resultados':
        case 'Provas':
            require_once(BASEPATH ."/novosite/nomade/framework/php/scrapeList.php");
            $scrape = new ScrapeList($pagesPerFile, $db, $region, $map, $fileType, $debug);
            break;
        case 'Info':
            require_once(BASEPATH ."/novosite/nomade/framework/php/scrapeInfo.php");
            $scrape = new ScrapeInfo($pagesPerFile, $db, $region, $map, $fileType, $debug);
            break;
        case 'Pos-graduacao':
            require_once(BASEPATH ."/novosite/nomade/framework/php/scrapePosGraduacao.php");
            $scrape = new ScrapePosGraduacao($pagesPerFile, $db, $region, $map, $fileType, $debug);
            break;
        case 'Cursos':
            require_once(BASEPATH ."/novosite/nomade/framework/php/scrapeCursos.php");
            $scrape = new ScrapeCursos($pagesPerFile, $db, $region, $map, $fileType, $debug);
            break;
        case 'CursosAllStates':
            require_once(BASEPATH ."/novosite/nomade/framework/php/scrapeCursosAllStates.php");
            $scrape = new ScrapeCursosAllStates($pagesPerFile, $db, $region, $map, $fileType, $debug);
            break;
        case 'Agenda':
            require_once(BASEPATH ."/novosite/nomade/framework/php/scrapeAgenda.php");
            $scrape = new ScrapeAgenda($pagesPerFile, $db, $region, $map, $fileType, $debug);
            break;
        case 'Glossario':
            require_once(BASEPATH ."/novosite/nomade/framework/php/scrapeGlossario.php");
            $scrape = new ScrapeGlossario($pagesPerFile, $db, $region, $map, $fileType, $debug);
            break;
    }

    $dir = $scrape->getDir();
    $basepath = $scrape->getBasepath();

    if ($fileType == "csv2xml") {
        if ($map == 'CursosAllStates') {
            $dir = $basepath."Cursos/csv/";
        } else if ($map == 'Glossario'){
            $dir = $basepath."Glossario/csv/";
        } else {
            $dir = "$basepath$region/csv/$map/";
        }

		$A_files = array();

		if ($handle = opendir($dir)) {
			while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != "..") {
					array_push($A_files, $file);
				}
			}
			closedir($handle);
		}

		$numFiles  = count($A_files);
		for($i=0; $i < $numFiles; $i++) {
			$fileName = $dir . $A_files[$i];
			$scrape->convertCSV2XML($fileName);
		}
    } else if ($fileType == "index") {
        $scrape->setIndexPage();
	} else {
		if ($single == '1') {
			$numFiles = 1;
		} else {
			$numFiles = $scrape->getNumFiles();
		}
		$fileNum = 1;

        for($count=0; $count < $numFiles; $count++) {
            $scrape->getScrape($fileNum);
            $fileNum++;
        }
	}
}

$smarty -> display("form_scrape.tpl");
?>