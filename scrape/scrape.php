<?php
abstract class Scrape {

	/**
    *  Sets debug mode
    *  @var bool
    */
	public $dm = 0;

	/**
    *  Sets total number of rows returned by query
    *  @var int
    */
	protected $totalRows = 0;

	/**
    *  Sets number of xml files to be written
    *  @var int
    */
	protected $numFiles = 1; // at least one file will be written

	/**
    *  tracks file number to append to xml file names to be written
    *  @var int
    */
	protected $fileNum = 1;

	/**
    *  Sets total number of pages per xml file created
    *  @var int
    */
	protected $pagesPerFile = 0;

	/**
    *  Sets current file being created
    *  @var int
    */
	protected $currentFile = 1;

	/**
    *  Sets offset for query limit
    *  @var int
    */
	protected $offset = 0;

	/**
    *  Sets query limit
    *  @var int
    */
	protected $limit = 1; // for testing

	/**
     * Holds the most recent error message.
     * @var string
     */
    protected $ErrorInfo = '';

	/**
     * Holds database object.
     * @var object
     */
	protected $db = '';

	/**
     * Holds section of the site (db table) being scraped.
     * @var string
     */
	protected $map = '';

	/**
     * Holds Brazilian state being scraped.
     * @var string
     */
	protected $region = '';

	/**
     * Holds Brazilian state ID being scraped.
     * @var string
     */
	protected $regionID = '';

	/**
     * Holds meta tag info.
     * @var string
     */
	 protected $metaDesc = '';
	 protected $metaKey = '';

	 /**
     * Holds site name and URL.
     * @var string
     */
	 protected $siteName = '';
	 protected $siteURL = '';

	 /**
     * Holds Brazilian state name.
     * @var string
     */
	 protected $stateName = '';
	 protected $stateInitials = '';

	 /**
     * Holds original URI for 301 redirect
     * @var string
     */
	 protected $oldPath = '';

	/**
     * directory path to save scrapes in the system
     * @var string
     */
	protected $basepath = '';

    /**
     * directory to generate files
     * @var string
     */
	protected $dir = '';

    /**
     * type of file to be generated
     * xml/csv/csv2xml
     * @var string
     */
     protected $fileType;



	function __construct($pagesPerFile, $db, $region, $map, $fileType, $debug = null) {
		if (!is_null($debug)) {
			$this->dm = $debug;
		}
		$this->debug('<h1>function __construct</h1>');
		$this->debug("limit: $this->limit <br />");

		$this->db = $db;
		$this->map = $map;
		if ($map == 'CursosAllStates') {
			$this->map = 'Cursos';
		}
		$this->region = $region;
		$this->setSiteInfo($region);
        $this->fileType = strtolower($fileType);

        // save scrapes here
        //$this->basepath = 'C:/hqp/scrapes_dir/scrapes_stage/';
        $this->basepath = 'C:/hqp/scrapes_dir/scrapes_prod/';

		$this->dir = "$this->basepath$this->region/$this->fileType/$this->map/";
        if ($map == 'CursosAllStates') {
			$this->dir = "$this->basepath$this->map/$this->fileType/";
		} else if ($map == 'Glossario') {
			$this->dir = "$this->basepath$this->map/$this->fileType/";
		}


		// website name
		$this->siteUmbrellaName = 'mundinho_guia';
		$this->siteURL = 'guia-vestibular.com.br';

		$this->pagesPerFile = $pagesPerFile;
		$this->setOffset();
		$this->setLimit();
		$query = $this->setTotalRowsQuery();
		$this->setTotalRows($query);
		$this->setNumFiles();

		$this->debug("pagesPerFile: $this->pagesPerFile <br />");
	}


	/**
	 * queries the database and
     * generates  XML/CSV file
	 *
	 * @return void
     */
	function getScrape($fileNum) {
		$this->debug('<h1>getScrape</h1>');
        $fileName = $this->dir . $this->map . $fileNum . '.' . $this->fileType;
		$query = $this->setQuery();
		$result = $this->query($query);
		//$this->debug_array($result);

		if ($this->fileType == 'xml') {
			$file = $this->setXML($result);
			$file = utf8_encode($file);
			$this->createXML($fileName, $file);
		} else if ($this->fileType == 'csv') {
			$file = $this->setSpreadsheet($result);
			$this->createSpreadsheet($fileName, $file);
		}
	}


	/**
     * queries db to scrape content
	 *
     * @access public
     * @return array
     */
	function query($query) {
		$this->currentFile++;
		$this->setOffset();

		$result = $this->db->select($query);
		return $result;
	}



	// ------------------------------------//
	// -------------- XML -----------------//
	// ------------------------------------//


    function setIndexPage() {
        $this->debug('<h2>=================== setIndexPage ===================</h2>');
        $this->debug("<h3>basepath: {$this->basepath}</h3>");
        $this->debug("<h3>oldPath: {$this->oldPath}</h3>");
        $this->debug("<h3>map: {$this->map}</h3>");
        $this->debug("<h3>stateName: {$this->stateName}</h3>");
        $this->debug("<h3>fileType: {$this->fileType}</h3>");

        $pathToIndex = "xml";

        //$metaKey = utf8_decode($this->getMetaKeyword($this->region));
        $metaKey = $this->getMetaKeyword($this->region);
        $metaDesc = $this->getIndexMetaDesc();
        $uri = strtolower($this->map)."/".strtolower($this->region);
        $old_uri = $this->oldPath . strtolower($this->map) . '.php';
        $currDateTime = date("d-M-Y H:i:s");


        if ($this->map == 'CursosAllStates' || $this->map == 'Cursos' || $this->map == 'Glossario') {
                $fileConverted = $this->basepath . $this->map . '/' . $pathToIndex . '/' . 'index.xml';
        } else {
                $fileConverted = $this->basepath . $this->region . '/' . $pathToIndex . '/' . $this->map . '/' . 'index.xml';
        }

        $this->debug("<h3>fileConverted: $fileConverted</h3>");


        $xml = '<?xml version="1.0" encoding="UTF-8"?>
<DirectoryFeed>
	<WEBSITE>
		<WEBSITE_NAME><![CDATA['.$this->siteUmbrellaName.']]></WEBSITE_NAME>
		<PORTAL_KEY><![CDATA[59600]]></PORTAL_KEY>
		<URLS>
			<URL><![CDATA['.$this->siteURL.']]></URL>
		</URLS>
		<PAGES>'.PHP_EOL;

        $xml .= "\t\t\t<PAGE>
				<PAGE_NAME><![CDATA[index.html]]></PAGE_NAME>
				<DIRECTORIES>
					<DIRECTORY
						DIR_DEFAULT_FLAG='Y'
						URI_TYPE='Primary'
						URI='/$uri'
						DISPLAY_NAME='".$this->region."' />
				</DIRECTORIES>".PHP_EOL;
                /*
                <ALTERNATE_PAGE_URIS>
					<URI URI_TYPE='301 Redirect' URI='$old_uri'/>
				</ALTERNATE_PAGE_URIS>".PHP_EOL;
                 *
                 */

             /*
			$xml .= "\t\t\t\t<ARTICLE Priority='1'>
					<ARTICLE_TYPE><![CDATA[Standard Article]]></ARTICLE_TYPE>
					<POSTING_DATE><![CDATA[$currDateTime]]></POSTING_DATE>".PHP_EOL;
              *
              */

			$xml .= "\t\t\t\t\t<META_TITLE><![CDATA[$this->map | $this->siteName]]></META_TITLE>
					<META_KEYWORD><![CDATA[".$metaKey."]]></META_KEYWORD>
					<META_DESC><![CDATA[".$metaDesc."]]></META_DESC>
                    <SEARCH_ENGINE_META_TAG><![CDATA[INDEX,FOLLOW]]></SEARCH_ENGINE_META_TAG>".PHP_EOL;;


            $xml .= "\t\t\t\t\t<SERVICES>
					   <SERVICE PRIMARY='1'><![CDATA[GENERAL_AUDIENCE_EVERYONE]]></SERVICE>
                    </SERVICES>".PHP_EOL;

            $xml .= "\t\t\t\t\t<SUBJECTS>
                        <SUBJECT PRIMARY='1'>
                            <![CDATA[SUBJECT]]>
                        </SUBJECT>
					</SUBJECTS>".PHP_EOL;

			$xml .= "\t\t\t\t\t<QUALIFICATIONS>
						<QUALIFICATION PRIMARY='1'>
							<![CDATA[UNDERGRADUATE]]>
						</QUALIFICATION>
					</QUALIFICATIONS>".PHP_EOL;

            $xml .= "\t\t\t\t\t<GEOGRAPHIES>
						<GEO
							COUNTRY='BR'
                            STATE='$this->stateInitials'
							PRIMARY='1'/>
					</GEOGRAPHIES>".PHP_EOL;
/**/
            $xml .= "\t\t\t\t</PAGE>".PHP_EOL;

        $xml .= "\t\t</PAGES>
	</WEBSITE>
</DirectoryFeed>";
        $this->debug($xml);
        //$file = utf8_encode($xml);
		$this->createXML($fileConverted, $xml);

    }


    /**
     * writes the XML file
	 *
	 * @param string $fileName
	 * @param string $content
     * @access private
     * @return void
     */
	function createXML($fileName, $content) {
		$this->debug('<h2>function createXML</h2>');
		$this->debug("fileName: $fileName<br />");
		$fp = fopen($fileName, 'wb');
		if (!is_resource($fp))
		{
			die("Cannot open $fileName");
		}
		fwrite($fp, $content);
		fclose($fp);
	}




	// ------------------------------------//
	// -------------- CSV -----------------//
	// ------------------------------------//



	/**
     * writes the CSV (spreadsheet) file
	 *
	 * @param string $fileName
	 * @param string $file
     * @access private
     * @return void
     */
	function createSpreadsheet($csv_file, $file) {
		$this->debug('<h1>createSpreadsheet</h1>');

		$fp = fopen($csv_file, 'w');
		if (!is_resource($fp))
		{
			die("Cannot open $csv_file");
		}
		foreach ($file as $fields) {
			fputcsv($fp, $fields);
		}
		fclose($fp);
	}


	/**
     * sets array for csv file
	 *
	 * @param array $result
     * @return array
     */
	function setSpreadsheet($result) {
		$this->debug('<h1>setSpreadsheet</h1>');

		// create the header row for cvs file
		$A_xls = array();
		$A_temp = array();
		foreach($result[0] as $k => $v) {
			$A_temp[] = $k;
		}
		$A_temp[] = 'MAP';
        $A_temp[] = 'META_TITLE';
        $A_temp[] = 'META_KEYWORD';
        $A_temp[] = 'META_DESC';
        $A_temp[] = 'SERVICES';
        $A_temp[] = 'SUBJECTS';
        $A_temp[] = 'QUALIFICATIONS';
        $A_temp[] = 'GEOGRAPHIES';
        $A_temp[] = 'LEADER';
        if ($this->map == 'Artigos' || $this->map == 'Noticias' || $this->map == 'Dicas') {
            $A_temp[] = 'CONTENT_TAGS';
        }

        array_push($A_xls, $A_temp);

        $A_contentTag = array('Enem', 'Prouni', 'Vestibular');

		// populate csv file
		$i = 0;
		foreach($result as $row) {
            if ($i > 2) $i = 0;
            $contentTag = $A_contentTag[$i];
            $i++;
            $fileName = $this->stripAccents($row['file_name']);
			$fileName = $this->removeSpecialCharacters($fileName);
			$fileName = $this->removeExtraDashes($fileName);
			$fileName = preg_replace('/^-/', '', $fileName);
			$fileName = preg_replace('/-$/', '', $fileName);
			$fileName .= '.html';
            $row['file_name'] = $fileName;
			$this->debug('<h1>fileName: '.$row['file_name'].'</h1>');
            //$this->debug_array($row);

			if ($this->map == 'Pos-graduacao') {
                $qualification = 'GRADUATE';
			} else {
				$qualification = 'UNDERGRADUATE';
			}

            $row['MAP'] = $this->map;

            $this->debug('<h1>article_body before: </h1>');
            $this->debug($row['article_body']);

            if ($row['article_body'] != '') {
                // check for unwanted line breaks
                if ($this->strpos_arr($row['article_body'], array("\r\n", "\r", "\n", "\t", chr(10), chr(13))))
                {
                    $this->debug('<h1>there are line breaks in the leader</h1>');
                }
                // remove unwanted line breaks
                if($body = str_replace(array("\r\n", "\r", "\n", "\t", chr(10), chr(13)), "", $row['article_body'])) {
                    $this->debug('<h1>DONE</h1>');
                }

                $body = strip_tags($body);
                //$body = strip_tags($row['article_body']);
                $body = $this->convertHTML2Text($body, "text");
                $body = $this->msword_conversion($body);
                $metaDesc = $this->TruncateString($body, 150);
                $leader = $this->TruncateString($body, 100);

                // check for unwanted line breaks
                if ($this->strpos_arr($row['article_body'], array("\r\n", "\r", "\n", "\t", chr(10), chr(13))))
                {
                    $this->debug('<h1>there are line breaks</h1>');
                }
                // remove unwanted line breaks
                if($article_body = str_replace(array("\r\n", "\r", "\n", "\t", chr(10), chr(13)), "", $row['article_body'])) {
                    $this->debug('<h1>DONE</h1>');
                }
                if($article_body = str_replace(array("\r\n", "\r", "\n", "\t", chr(10), chr(13)), "", $article_body)) {
                    $this->debug('<h1>DONE again</h1>');
                }

                if ($this->strpos_arr($article_body, array("\r\n", "\r", "\n", "\t", chr(10), chr(13))))
                {
                    $this->debug('<h1>there are STILL line breaks</h1>');
                }

                //$article_body = nl2br($article_body);

                $article_body = $this->msword_conversion($article_body);
                //$article_body = $this->msword_conversion($row['article_body']);
                $article_body = $this->convertHTML2Text($article_body, "text");
            } else {
                $article_body = '&nbsp;';
                $metaDesc = '&nbsp;';
                $leader = '&nbsp;';

            }

            $this->debug('<h1>article_body after: </h1>');
            $this->debug($article_body);
            $this->debug('<hr />');


            //$body = strip_tags($row['article_body']);
			//$body = $this->stripAccents($body);

			//$metaDesc = $body;
            //$metaDesc = $this->msword_conversion($metaDesc);
			//$metaDesc = $this->convertHTML2Text($metaDesc, "text");
			//$metaDesc = $this->TruncateString($metaDesc, 150);
			$metaKey = $this->getMetaKeyword($this->region);
            $row['META_TITLE'] = $row['title']." | $this->siteName";
            $row['META_KEYWORD'] = $metaKey;
            $row['META_DESC'] = $metaDesc;



			//$article_body = $this->msword_conversion($row['article_body']);
            //$article_body = $this->convertHTML2Text($article_body, 'text');
            $row['article_body'] = $article_body;

            $row['SERVICES'] = '[GENERAL_AUDIENCE_EVERYONE]|UNIVERSITY_COLLEGE';
            $row['SUBJECTS'] = '[SUBJECT]';
            $row['QUALIFICATIONS'] = "[$qualification]";
            $row['GEOGRAPHIES'] = '';
            $row['LEADER'] = $leader;
            /*
            if ($this->map == 'Artigos' || $this->map == 'Noticias' || $this->map == 'Dicas') {
                $row['CONTENT_TAGS'] = $contentTag;
            }
            */
            $row['CONTENT_TAGS'] = '';

            array_push($A_xls, $row);
		}

		return $A_xls;
	}



	// --------------------------------------------------------//
	// -------------- convert from csv to xml -----------------//
	// --------------------------------------------------------//


	function convertCSV2XML($fileToConvert) {

		$A_row = array();
		if (($handle = fopen($fileToConvert, 'r')) !== FALSE) {
			while (($data = fgetcsv($handle, 20000, ',')) !== FALSE) {
				array_push($A_row, $data);
			}
			fclose($handle);
		}

		// set array that only contains tag names
		$A_keys = array_slice($A_row, 0,1);

		// remove first row as it only contains tag names
		$A_row = array_slice($A_row, 1);

		// split results into $numFiles
		$A_split = array_chunk($A_row, $this->pagesPerFile);

		$numFiles = count($A_split);

		// create xml docs
		for ($i = 0; $i < $numFiles; $i++) {
            $this->debug("<h1>------------ xml file $i ------------</h1>");
			// array that will generate xml files
			$A_combined = array();

			// xml file name
            if ($this->map != 'CursosAllStates') {
                $fileConverted = $this->dir . $this->map . '_fromCSV' . $this->fileNum .'.xml';
            } else {
                $fileConverted = $this->basepath .  "Cursos/$this->fileType/" . $this->map . '_fromCSV' . ($i+1) .'.xml';
            }

            $this->debug("<h2>fileConverted: $fileConverted</h2>");

            $arrRow = array();



			foreach ($A_split[$i] as $row) {
                $this->debug("<h3> >>>>row BEFORE<<<< </h3>");
                $this->debug_array($row);

                $this->debug("<h3> >>>>row AFTER<<<< </h3>");
                array_walk($row, "test_empty");
                $this->debug_dump($row);

                $this->debug("<h3> >>>>===A_keys===<<<< </h3>");
                $this->debug_dump($A_keys[0]);

                $combined = array_combine($A_keys[0], $row);
                $this->debug("<h3> >>>>combined<<<< </h3>");
                $this->debug_array($combined);

                array_push($A_combined, $combined);
			}

            $this->debug("<h3> >>>>---  A_combined   <<<< </h3>");
            $this->debug_array($A_combined);

			$file = $this->setXMLfromCVS($A_combined);
            $file = utf8_encode($file);
			$this->createXML($fileConverted, $file);

			$this->fileNum++;
		}
	}





    /**
     * builds the XML file from csv
	 *
	 * @param array $result
     * @return string
     */
	function setXMLfromCVS($result) {
		$this->debug('<h2>=================== setXMLfromCVS ===================</h2>');

        // uri
        if (strtolower($this->map) != 'cursos') {
                $uri = strtolower($this->map).'/'.strtolower($this->region);
            } else {
                $uri = 'cursos';
            }


		if ($this->map == 'Gabaritos' || $this->map == 'Resultados' || $this->map == 'Provas') {
			$article_type = 'External Link';
		} else {
			$article_type = 'Standard Article';
		}


		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<DirectoryFeed>
	<WEBSITE>
		<WEBSITE_NAME><![CDATA['.$this->siteUmbrellaName.']]></WEBSITE_NAME>
		<PORTAL_KEY><![CDATA[59600]]></PORTAL_KEY>
		<URLS>
			<URL><![CDATA['.$this->siteURL.']]></URL>
		</URLS>
		<PAGES>'.PHP_EOL;
        foreach($result as $row) {
			$this->debug_array($row);
			//$this->debug("<hr />");
			$old_uri = $this->oldPath . strtolower($this->map) . '_ler.php?id=' . $row['TB_MATERIA_ID'];
			if (strpos($row['META_TITLE']," | $this->siteName") === false) {
                $row['META_TITLE'] .= " | $this->siteName";
            }


			$data_publicacao = date("d-M-Y H:i:s", strtotime($row['DATA_PUBLICACAO']));
			$meta_keyword = trim($row['META_KEYWORD']);

            $xml .= "\t\t\t"."<PAGE>
				<PAGE_NAME><![CDATA[".$row['file_name']."]]></PAGE_NAME>
				<DIRECTORIES>
					<DIRECTORY
						DIR_DEFAULT_FLAG='N'
						URI_TYPE='Primary'
						URI='/$uri'
						DISPLAY_NAME='".$this->region."' />
				</DIRECTORIES>
				<ALTERNATE_PAGE_URIS>
					<URI URI_TYPE='301 Redirect' URI='$old_uri'/>
				</ALTERNATE_PAGE_URIS>".PHP_EOL;

			$xml .= "\t\t\t\t<ARTICLE Priority='1'>
					<ARTICLE_TYPE><![CDATA[$article_type]]></ARTICLE_TYPE>
					<POSTING_DATE><![CDATA[$data_publicacao]]></POSTING_DATE>".PHP_EOL;
					//<POSTING_TIME><![CDATA[$posting_time]]></POSTING_TIME>
			$xml .= "\t\t\t\t\t<TITLE><![CDATA[".$row['title']."]]></TITLE>".PHP_EOL;
            //if ($row['LEADER'] != "") {
                $xml .= "\t\t\t\t\t<LEADER><![CDATA[".$row['LEADER']."]]></LEADER>".PHP_EOL;
            //}
			$xml .= "\t\t\t\t\t<FILE_NAME><![CDATA[".$row['file_name']."]]></FILE_NAME>
					<META_TITLE><![CDATA[".$row['META_TITLE']."]]></META_TITLE>
					<META_KEYWORD><![CDATA[".$meta_keyword."]]></META_KEYWORD>
					<META_DESC><![CDATA[".$row['META_DESC']."]]></META_DESC>
                    <SEARCH_ENGINE_META_TAG><![CDATA[INDEX,FOLLOW]]></SEARCH_ENGINE_META_TAG>".PHP_EOL;


			$xml .= "\t\t\t\t\t<LINK_TEXT><![CDATA[".$row['title']."]]></LINK_TEXT>".PHP_EOL;

			$xml .= "\t\t\t\t\t<INHERIT_FLAG><![CDATA[Y]]></INHERIT_FLAG>".PHP_EOL;

			$xml .= "\t\t\t\t\t<BODY><![CDATA[".$row['article_body']."]]></BODY>".PHP_EOL;

            /*	<LINK_TEXT><![CDATA[]]></LINK_TEXT>*/

			/*
				$xml .= "<CONTENT_TAGS>
						<TAG>
							<TAG_NAME><![CDATA[]]></TAG_NAME>
							<CATEGORY><![CDATA[Education]]></CATEGORY>
						</TAG>
					</CONTENT_TAGS>".PHP_EOL;
            */

            /* --- taxonomy --- */

			if ($row['SERVICES'] != "") {
				$xml .= "\t\t\t\t\t<SERVICES>".PHP_EOL;
				$A_services = explode("|", $row['SERVICES']);
				foreach($A_services as $service) {
					if (strpos($service, "[") > -1) {
						$primary = " PRIMARY='1'";
						$service = str_replace("[", "", $service);
						$service = str_replace("]", "", $service);
					} else {
						$primary = "";
					}
					$service = trim($service);
					$xml .= "\t\t\t\t\t\t<SERVICE$primary><![CDATA[$service]]></SERVICE>".PHP_EOL;
				}
                $xml .= "\t\t\t\t\t</SERVICES>".PHP_EOL;
            }

			if (array_key_exists('SUBJECTS', $row) && $row['SUBJECTS'] != "") {
				$xml .= "\t\t\t\t\t<SUBJECTS>".PHP_EOL;
				$A_subjects = explode("|", $row['SUBJECTS']);
				foreach($A_subjects as $subject) {
					if (strpos($subject, "[") > -1) {
						$primary = " PRIMARY='1'";
						$subject = str_replace("[", "", $subject);
						$subject = str_replace("]", "", $subject);
					} else {
						$primary = "";
					}
					$subject = trim($subject);
					$xml .= "\t\t\t\t\t\t<SUBJECT$primary><![CDATA[$subject]]></SUBJECT>".PHP_EOL;
				}
				$xml .= "\t\t\t\t\t</SUBJECTS>".PHP_EOL;
			}

			if (array_key_exists('QUALIFICATIONS', $row) && $row['QUALIFICATIONS'] != "") {
				$xml .= "\t\t\t\t\t<QUALIFICATIONS>".PHP_EOL;
				$A_qualifications = explode("|", $row['QUALIFICATIONS']);
				foreach($A_qualifications as $qualification) {
					if (strpos($qualification, "[") > -1) {
						$primary = " PRIMARY='1'";
						$qualification = str_replace("[", "", $qualification);
						$qualification = str_replace("]", "", $qualification);
					} else {
						$primary = "";
					}
					$qualification = trim($qualification);
					$xml .= "\t\t\t\t\t\t<QUALIFICATION$primary>
                            <![CDATA[$qualification]]>
                        </QUALIFICATION>".PHP_EOL;
				}
				$xml .= "\t\t\t\t\t</QUALIFICATIONS>".PHP_EOL;
            }

            $xml .= "\t\t\t\t\t<GEOGRAPHIES>
						<GEO
							COUNTRY='BR'
                            STATE='$this->stateInitials'
							PRIMARY='1'/>
					</GEOGRAPHIES>".PHP_EOL;

            if (array_key_exists('CONTENT_TAGS', $row) && $row['CONTENT_TAGS'] != "") {
                $contentTag = $row['CONTENT_TAGS'];
                $xml .= "\t\t\t\t\t<CONTENT_TAGS>
                        <TAG>
                            <TAG_NAME><![CDATA[$contentTag]]></TAG_NAME>
                            <CATEGORY><![CDATA[Education]]></CATEGORY>
                        </TAG>
                    </CONTENT_TAGS>".PHP_EOL;
			}


            /* --- photo --- */

            $T_photo = array();
			$A_photo = array();
            $T_photo = $this->getPhoto($row['TB_MATERIA_ID']);
            //$T_photo = $this->getPhoto(2558);
            $isPhoto = count($T_photo) > 0 ? true : false;
			if ($isPhoto) {
				$this->debug('<h2>isPhoto</h2>');
				$A_photo = $T_photo[0];
				$alt = $A_photo['LEGENDA'] != '' ? $A_photo['LEGENDA'] : $A_photo['TITULO'];

                $xml .= "\t\t\t\t\t<IMAGES>".PHP_EOL;
                    if ($A_photo['FOTO_PEQUENA'] != '') {
                        $xml .= "\t\t\t\t\t\t<IMAGE>
							<PATH><![CDATA[".$A_photo['FOTO_PEQUENA']."]]></PATH>
							<ALT><![CDATA[$alt]]></ALT>
							<IMAGE_TYPE><![CDATA[Thumbnail]]></IMAGE_TYPE>
							<IS_REMOTE_IMAGE><![CDATA[N]]></IS_REMOTE_IMAGE>
						</IMAGE>".PHP_EOL;
                    }

                    if ($A_photo['FOTO_MEDIA'] != '') {
                        $xml .= "\t\t\t\t\t<IMAGE>
							<PATH><![CDATA[".$A_photo['FOTO_MEDIA']."]]></PATH>
							<ALT><![CDATA[$alt]]></ALT>
							<IMAGE_TYPE><![CDATA[Feature]]></IMAGE_TYPE>
							<IS_REMOTE_IMAGE><![CDATA[N]]></IS_REMOTE_IMAGE>
						</IMAGE>".PHP_EOL;
                    }

                    if ($A_photo['FOTO_GRANDE'] != '') {
                        $xml .= "\t\t\t\t\t\t<IMAGE>
							<PATH><![CDATA[".$A_photo['FOTO_GRANDE']."]]></PATH>
							<ALT><![CDATA[$alt]]></ALT>
							<IMAGE_TYPE><![CDATA[Portrait]]></IMAGE_TYPE>
							<IS_REMOTE_IMAGE><![CDATA[N]]></IS_REMOTE_IMAGE>
						</IMAGE>".PHP_EOL;
                    }
				$xml .= "\t\t\t\t\t</IMAGES>".PHP_EOL;
            }

			$xml .= "\t\t\t\t</ARTICLE>
			</PAGE>".PHP_EOL;
        }
        $xml .= "\t\t</PAGES>
	</WEBSITE>
</DirectoryFeed>";
		return $xml;
	}








	// ----------------------------------------------------//
	// -------------- setters and getters -----------------//
	// ----------------------------------------------------//




	/**
    *  sets site url, name and regionID
	*
	*  @param string $region
    *  @return void
    */
	function setSiteInfo($region) {
		switch($region) {
			case 'minas_gerais':
				$this->regionID = 1;
				$this->stateURL = "http://www.minasvestibular.com.br";
				$this->siteName = 'MinasVestibular.com.br';
				$this->stateName = 'Minas Gerais';
				$this->stateInitials = 'MG';
				$this->oldPath = '/novosite/';
				break;
			case 'rio_de_janeiro':
				$this->regionID = 2;
				$this->stateURL = "http://www.riovestibular.com.br";
				$this->siteName = 'RioVestibular.com.br';
				$this->stateName = 'Rio de Janeiro';
				$this->stateInitials = 'RJ';
				$this->oldPath = '/novosite/';
				break;
			case 'sao_paulo':
				$this->regionID = 3;
				$this->stateURL = "http://www.saopaulovestibular.com.br";
				$this->siteName = 'SaoPauloVestibular.com.br';
				$this->stateName = 'S&atilde;o Paulo';
				$this->stateInitials = 'SP';
				$this->oldPath = '/';
				break;
		}
	}

	/**
     * Sets the total number of rows retrieved by the query
	 *
	 * @param string $map
     * @access public
     * @return void
     */
	function setTotalRows($query) {
		$this->debug('setTotalRows base<br />');
		$result = $this->db->select($query, true);
        $this->debug_array($result);
		$this->totalRows = $result[0]['totalRows'];
		$this->debug("totalRows: $this->totalRows <br />");
	}

	/**
     * Sets query for the total number of articles
	 * to be implemented by children
	 * @access public
     * @return void
     */
	function setTotalRowsQuery() {}

	/**
     * Sets query for the total number of articles
	 * to be implemented by children
	 * @access public
     * @return void
     */
	function setQuery() {}


	/**
     * builds the XML file to be created from database queries
	 * to be implemented by children
	 * @param array $result
     * @access public
     * @return string
     */
	function setXML($result) {}



    /**
    *  returns directory to generate files
	*
    * @access public
    * @return string
    */
    function getDir() {
        return $this->dir;
    }


    /**
    *  returns path to scrapes in the system
	*
    * @access public
    * @return string
    */
    function getBasepath() {
        return $this->basepath;
    }



	/**
    *  returns meta keyword
	*
    *  @return string
    */
	function getMetaKeyword($region) {
		switch($region) {
			case 'minas_gerais':
				$metaState = 'Minas Gerais, Minas, MG,';
				$stateKey = 'faculdade mg,faculdade minas, faculdade minas gerais,universidade minas gerais,universidade minas,faculdades minas gerais,universidade de minas gerais,universidades de minas gerais,universidades minas gerais,faculdades em minas gerais,universidades em minas gerais,faculdades de minas gerais,faculdade em minas gerais,faculdade de minas gerais, mestrado minas gerais, faculdades mg';
				break;
			case 'rio_de_janeiro':
				$metaState = 'Rio de Janeiro, Rio, RJ,';
				$stateKey = 'faculdade rio de janeiro,universidade rio de janeiro,universidade rio,faculdades rio de janeiro,universidade do rio de janeiro,universidades do rio de janeiro,universidades rio de janeiro,faculdades no rio de janeiro,universidades no rio de janeiro,faculdades do rio de janeiro,faculdade no rio de janeiro,faculdade do rio de janeiro,mestrado rio de janeiro,	faculdades rj';
				break;
			case 'sao_paulo':
				$metaState = 'Sao Paulo, São Paulo, SP,';
				$stateKey = 'faculdade são paulo,faculdade sao paulo,faculdades em sao paulo,faculdades de sao paulo,faculdades sao paulo,universidade de são paulo,faculdades sp,faculdade sp,universidade são paulo,faculdades em sp,faculdade em são paulo,faculdade de são paulo,universidades de são paulo';
				break;
		}
		// default keys
		$metaKey = 'vestibular,enem,prouni,universidade,faculdade,';

		// shuffle default keys to avoid
		// always repeating in the same order
		$A_metaKey = explode(',',$metaKey);
		shuffle($A_metaKey);
		$metaKey = $metaState .  implode(',', $A_metaKey);

		// random keys
		$A_randomKeys = array('inscrições enem 2011', 'resultado enem', 'resultado do enem', 'prova vestibular',  'faculdades', 'faculdade online', 'mestrado', 'vestibulares 2011', 'cursos faculdade', 'pos graduação','faculdades reconhecidas pelo mec','vestibular 2011','educação a distancia','graduação a distância');
		$numRandKeys = count($A_randomKeys);
		$A_addKeys = array();

		while (count($A_addKeys) < 5) {
			$randNum = rand(0, $numRandKeys-1);
			if (!in_array($A_randomKeys[$randNum], $A_addKeys)) {
				array_push($A_addKeys, $A_randomKeys[$randNum]);
			}
		}

		// state specific keys
		$A_stateKey = explode(",",$stateKey);
		$numRandKeys = count($A_stateKey);
		$A_addStateKeys = array();
		if ($numRandKeys > 2) {
			while (count($A_addStateKeys) < 4) {
				$randNum = rand(0, $numRandKeys-1);
				if (!in_array($A_stateKey[$randNum], $A_addStateKeys)) {
					array_push($A_addStateKeys, $A_stateKey[$randNum]);
				}
			}
		} else {
			$A_addStateKeys = $A_stateKey;
		}
		//$this->debug_array($A_addStateKeys);

		// add reandom keys and state keys to meta keys
		$metaKey .= "," . implode(",", $A_addKeys). "," . implode(",", $A_addStateKeys);
		return $metaKey;
	}

    /**
     * returns meta description for section index pages
     *
     * @return string
     */
    function getIndexMetaDesc() {
        switch ($this->map) {
            case "Agenda":
               $metaDesc = "Agenda de universidades e faculdades em {$this->stateName}";
               break;
           case "Artigos":
               $metaDesc = "Artigos sobre universidades e faculdades em {$this->stateName}";
               break;
           case "Dicas":
               $metaDesc = "Lista de dicas para o vestibular em {$this->stateName}";
               break;
           case "Faculdades":
               $metaDesc = "Lista de faculdades em {$this->stateName}";
               break;
           case "Gabaritos":
               $metaDesc = "Lista de gabaritos de provas para o vestibular em {$this->stateName}";
               break;
           case "Noticias":
               $metaDesc = "Lista de noticias sobre e para o vestibular em {$this->stateName}";
               break;
           case "Pos-graduacao":
               $metaDesc = "Lista de noticias sobre pos-graduacao em {$this->stateName}";
               break;
           case "Provas":
               $metaDesc = "Exemplos de provas para o vestibular em {$this->stateName}";
               break;
           case "Resultados":
               $metaDesc = "Lista de resultados de provas para o vestibular em {$this->stateName}";
               break;
           case "Cursos":
           case "CursosAllStates":
               $metaDesc = "Lista de cursos em {$this->stateName}";
               break;
           case "Glossario":
               $metaDesc = "Glossário de termos relacionados a educação em {$this->stateName}";
               break;
        }
        return $metaDesc;
    }


	/**
    *  returns meta description
	*
    *  @return string
    */
	public function getMetaDscription() {
		return $this->metaDesc;
	}


	/**
    *  Sets offset for query limit
    *  @return int
    */
	function setOffset() {
		$this->offset = ($this->currentFile - 1) * $this->pagesPerFile;
		$this->debug("offset: $this->offset <br />");
	}

	/**
    *  Sets query limit
    *  @return int
    */
	function setLimit() {
        $this->debug('<h2>setLimit</h2>');
		$this->limit = $this->offset + $this->pagesPerFile;
		$this->debug("limit: $this->limit <br />");
	}

	/**
    *  Sets number of xml files to be written
    *  @return int
    */
	function setNumFiles() {
		$this->numFiles = $this->calcNumFiles($this->totalRows, $this->pagesPerFile);
		$this->debug("numFiles: $this->numFiles <br />");
	}

	/**
    *  Gets number of xml files to be written
    *  @return int
    */
	function getNumFiles() {
		return $this->numFiles;
	}

	/**
     * Calculates the total number of xml files to be written
	 *
     * @param int $total
     * @param int $limit
     * @return int
    */
	function calcNumFiles($total, $limit) {
		$this->debug("numFiles: total: $total / limit: $limit <br />");
		if ($total <= $limit) return 1;
		else {
			$resto = ($total%$limit);

			if ($resto > 0) {
				return (floor($total/$limit) + 1);
			} else
				return floor($total/$limit);
		}
	}

	/**
     * Gets the total number of rows retrieved by the query
	 *
	 * @return int
     */
	function getTotalRows() {
		return $this->totalRows;
	}

	function getPhoto($id) {
        $this->debug('<h1>getPhoto</h1>');
		$query = "SELECT
			F.TB_FOTO_ID,
			F.LEGENDA,
			F.TITULO,
			F.CREDITOS,
			F.FOTO_PEQUENA,
			F.FOTO_MEDIA,
			F.FOTO_GRANDE,
			Z.DESCRICAO AS ZONA_EXIBICAO
			FROM TB_FOTO F
			INNER JOIN TB_MATERIA_TB_FOTO MF ON (F.TB_FOTO_ID = MF.TB_FOTO_ID)
			LEFT OUTER JOIN TB_ZONA_EXIBICAO Z ON (MF.TB_ZONA_EXIBICAO_ID = Z.TB_ZONA_EXIBICAO_ID)
			WHERE MF.TB_MATERIA_ID = $id
			ORDER BY MF.ORDEM";

        //$this->debug("$query <br />");
		$result = array();
		$result = $this->db->select($query);
		return $result;
	}




	// ----------------------------------------------//
	// -------------- Miscellaneous -----------------//
	// ----------------------------------------------//




	/*
     * checks if article is about pos-graduação
	 * for qualifications taxonomy
	 *
	 * @param int $id
	 * @return boolean
     */
	function isPos($id) {
		//$this->debug('<h1>isPos</h1>');
		$query = "SELECT
		M.TB_MATERIA_ID,
		M.TITULO_MATERIA
		FROM TB_MATERIA M, TB_MATERIA_TB_EDITORIA ME
		WHERE M.TB_MATERIA_ID = ME.TB_MATERIA_ID
		AND ME.TB_EDITORIA_ID = 65
		AND M.TB_MATERIA_ID = $id";

		//$this->debug("$query <br />");
		$result = $this->db->select($query);
		//$this->debug_array($result);
		if (!empty($result)) {
			return true;
		}
		return false;
	}

	function debug($msg) {
		if (0 < $this->dm) {
			echo "$msg";
		}
	}

	function debug_array($arr) {
		if (0 < $this->dm) {
			echo "<pre>";print_r($arr);echo "</pre>";
            echo '<br />';
		}
	}

    function debug_dump($data)
    {
        if (0 < $this->dm) {
			var_dump($data);
            echo '<br />';
		}

    }


	/**
	 * Removes consecutive dashes from string
	 *
	 * @name		removeExtraDashes
	 * @param 		string $string to convert
	 * @return		string
	 *
	 */
	function removeExtraDashes($string) {
		$str = preg_replace('/\-\-+/', '-', $string);
		return $str;
	}

	/**
	 * Strips non-string and non-number characters from string
	 *
	 * @name		removeSpecialCharacters
	 * @param 		string $string to convert
	 * @return		string
	 *
	 */
	function removeSpecialCharacters($string) {
		$str = preg_replace('/[^a-z0-9\.\- ]/', '', $string);
		return $str;
	}

	/**
	 * Strips foreign languages accents from string
	 *
	 * @name		stripAccents
	 * @param 		string $string to convert
	 * @return		string
	 *
	 */

	function stripAccents($string){
        $string = utf8_encode($string);
		//, '', '', '', '', '', '', '', '', '', ''
        $accents = array('À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'A', 'A', 'A', 'Æ', 'Ç', 'C', 'C', 'C', 'C', 'D', 'Ð', 'È', 'É', 'Ê', 'Ë', 'E', 'E', 'E', 'E', 'E', 'G', 'G', 'G', 'G', 'H', 'H', 'Ì', 'Í', 'Î', 'Ï', 'I', 'I', 'I', 'I', 'I', '?', 'J', 'K', 'L', 'L', 'L', 'L', '?', 'Ñ', 'N', 'N', 'N', '?', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'Ø', 'O', 'O', 'O', 'Œ', 'R', 'R', 'R', 'S', 'Š', 'S', 'S', '?', 'T', 'T', 'T', '?', 'Ù', 'Ú', 'Û', 'Ü', 'U', 'U', 'U', 'U', 'U', 'U', 'W', 'Ý', 'Y', 'Ÿ', 'Z', 'Ž', 'Z', 'à', 'á', 'â', 'ã', 'ä', 'å', 'a', 'a', 'a', 'æ', 'ç', 'c', 'c', 'c', 'c', 'd', 'd', 'è', 'é', 'ê', 'ë', 'e', 'e', 'e', 'e', 'e', 'ƒ', 'g', 'g', 'g', 'g', 'h', 'h', 'ì', 'í', 'î', 'ï', 'i', 'i', 'i', 'i', 'i', '?', 'j', 'k', '?', 'l', 'l', 'l', 'l', '?', 'ñ', 'n', 'n', 'n', '?', '?', 'ò', 'ó', 'ô', 'õ', 'ö', 'ø', 'o', 'o', 'o', 'œ', 'r', 'r', 'r', 's', 'š', 's', 's', '?', 't', 't', 't', '?', 'ù', 'ú', 'û', 'ü', 'u', 'u', 'u', 'u', 'u', 'u', 'w', 'ý', 'ÿ', 'y', 'ž', 'z', 'z', 'Þ', 'þ', 'ß', '?', 'Ð', 'ð');

        $letters = array('A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'AE', 'C', 'C', 'C', 'C', 'C', 'D', 'D', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'G', 'G', 'G', 'G', 'H', 'H', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'I', 'J', 'J', 'K', 'L', 'L', 'L', 'L', 'L', 'N', 'N', 'N', 'N', 'N', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'E', 'R', 'R', 'R', 'S', 'S', 'S', 'S', 'S', 'T', 'T', 'T', 'T', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'W', 'Y', 'Y', 'Y', 'Z', 'Z', 'Z', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'c', 'c', 'c', 'c', 'c', 'd', 'd', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'f', 'g', 'g' ,'g', 'g', 'h', 'h', 'i', 'i', 'i', 'i', 'i', 'i', 'i','i', 'i', 'j', 'j', 'k', 'k', 'l', 'l', 'l', 'l' ,'l' ,'n', 'n', 'n', 'n', 'n', 'n', 'o', 'o', 'o', 'o' ,'o', 'o', 'o', 'o' ,'o', 'e', 'r' ,'r' ,'r' ,'s', 's', 's' ,'s', 's', 't' ,'t' ,'t' ,'t' ,'u' ,'u', 'u' ,'u' ,'u', 'u' ,'u' ,'u' ,'u' ,'u' ,'w', 'y', 'y', 'y', 'z', 'z', 'z', 'T', 't', 'B', 'f','D', 'd');
		$string = str_replace($accents, $letters, $string);
        //$this->debug("new string: ($string)");
		return $string;
	}

    function cleanForShortURL($toClean) {
        $normalizeChars = array(
        'Š'=>'S', 'š'=>'s', 'Ð'=>'Dj','Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A',
        'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I',
        'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U', 'Ú'=>'U',
        'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss','à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a',
        'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i',
        'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
        'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y', 'ƒ'=>'f'
    );
        $toClean     =     str_replace('&', '-and-', $toClean);
        $toClean     =    trim(preg_replace('/[^\w\d_ -]/si', '', $toClean));//remove all illegal chars
        $toClean     =     str_replace(' ', '-', $toClean);
        $toClean     =     str_replace('--', '-', $toClean);

        return strtr($toClean, $normalizeChars);
    }


	/**
	 * Converts html into plain text and vice-versa
	 *
	 * @name		convertHTML2Text
	 * @param 		string $string to convert
	 * @param 		string $convertTo to set type of conversion
	 * @return		string
	 *
	 */
	function convertHTML2Text ($string, $convertTo) {
        //$string = utf8_encode($string);
		$html = array("&Agrave;", "&Aacute;", "&Acirc;", "&Atilde;", "&Auml;", "&Aring;", "&AElig;", "&Ccedil;", "&Egrave;", "&Eacute;", "&Ecirc;", "&Euml;", "&Igrave;", "&Iacute;", "&Icirc;", "&Iuml;", "&ETH;", "&Ntilde;", "&Ograve;", "&Oacute;", "&Ocirc;", "&Otilde;", "&Ouml;", "&times;", "&Oslash;", "&Ugrave;", "&Uacute;", "&Ucirc;", "&Uuml;", "&Yacute;", "&THORN;", "&szlig;", "&agrave;", "&aacute;", "&acirc;", "&atilde;", "&auml;", "&aring;", "&aelig;", "&ccedil;", "&egrave;", "&eacute;", "&ecirc;", "&euml;", "&igrave;", "&iacute;", "&icirc;", "&iuml;", "&eth;", "&ntilde;", "&ograve;", "&oacute;", "&ocirc;", "&otilde;", "&ouml;", "&divide;", "&oslash;", "&ugrave;", "&uacute;", "&ucirc;");

		$letters = array("À", "Á", "Â", "Ã", "Ä", "Å", "Æ", "Ç", "È", "É", "Ê", "Ë", "Ì", "Í", "Î", "Ï", "Ð", "Ñ", "Ò", "Ó", "Ô", "Õ", "Ö", "×", "Ø", "Ù", "Ú", "Û", "Ü", "Ý", "Þ", "ß", "à", "á", "â", "ã", "ä", "å", "æ", "ç", "è", "é", "ê", "ë", "ì", "í", "î", "ï", "ð", "ñ", "ò", "ó", "ô", "õ", "ö", "÷", "ø", "ù", "ú", "û");

		if ($convertTo == "text") {
			$string = str_replace($html, $letters, $string);
		} else {
			$string = str_replace($letters, $html, $string);
		}

		return $string;
	}

	/**
	 * Truncates strings
	 *
	 * @name		TruncateString()
	 * @param 		string $string to truncate
	 * @param 		int $limit characters to truncate
	 * @param 		space or punctuation $break to finish truncating before $limit
	 * @param 		string $pad to append to truncated string
	 * @return		string
	 *
	 */
	function TruncateString($string, $limit, $break=" ", $pad="...") {
		// return with no change if string is shorter than $limit
		$string = strip_tags($string);
		if(strlen($string) <= $limit) {
			return $string;
		}
		$string = substr($string, 0, $limit);
		if(false !== ($breakpoint = strrpos($string, $break))) {
			$string = substr($string, 0, $breakpoint);
		}
		return $string . $pad;
	}

	/**
	 * Converts MS Word characters into ASCII
	 *
	 * @name		msword_conversion
	 * @param 		string $str to cotnvert
	 * @return		string
	 *
	 */
	function msword_conversion($str)
    {
        $str = str_replace(chr(130), ',', $str);    // baseline single quote
        $str = str_replace(chr(131), 'NLG', $str);  // florin
        $str = str_replace(chr(132), '"', $str);    // baseline double quote
        $str = str_replace(chr(133), '...', $str);  // ellipsis
        $str = str_replace(chr(134), '**', $str);   // dagger (a second footnote)
        $str = str_replace(chr(135), '***', $str);  // double dagger (a third footnote)
        $str = str_replace(chr(136), '^', $str);    // circumflex accent
        $str = str_replace(chr(137), 'o/oo', $str); // permile
        $str = str_replace(chr(138), 'Sh', $str);   // S Hacek
        $str = str_replace(chr(139), '<', $str);    // left single guillemet
        // $str = str_replace(chr(140), 'OE', $str);   // OE ligature
        $str = str_replace(chr(145), "'", $str);    // left single quote
        $str = str_replace(chr(146), "'", $str);    // right single quote
        // $str = str_replace(chr(147), '"', $str);    // left double quote
        // $str = str_replace(chr(148), '"', $str);    // right double quote
        $str = str_replace(chr(149), '-', $str);    // bullet
        $str = str_replace(chr(150), '-–', $str);    // endash
        $str = str_replace(chr(151), '--', $str);   // emdash
        // $str = str_replace(chr(152), '~', $str);    // tilde accent
        // $str = str_replace(chr(153), '(TM)', $str); // trademark ligature
        $str = str_replace(chr(154), 'sh', $str);   // s Hacek
        $str = str_replace(chr(155), '>', $str);    // right single guillemet
        // $str = str_replace(chr(156), 'oe', $str);   // oe ligature
        $str = str_replace(chr(159), 'Y', $str);    // Y Dieresis
        $str = str_replace('°C', '&deg;C', $str);    // Celcius is used quite a lot so it makes sense to add this in
        $str = str_replace('£', '&pound;', $str);
        $str = str_replace("'", "'", $str);
        $str = str_replace('"', '"', $str);
        $str = str_replace('–', '&ndash;', $str);

        return $str;
    }

	// strpos that takes an array of values to match against a string
    // note the stupid argument order (to match strpos)
    function strpos_arr($haystack, $needle) {
        if(!is_array($needle)) $needle = array($needle);
        foreach($needle as $what) {
            if(($pos = strpos($haystack, $what))!==false) return $pos;
        }
        return false;
    }
}
function test_empty(&$item1, $key)
{
    if ($item1 == '')
    {
        $item1 = '&nbsp;';
    }
}
?>