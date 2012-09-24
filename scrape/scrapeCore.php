<?php

//include './scrape.php';
require_once(BASEPATH .'/novosite/nomade/framework/php/scrape.php');

class ScrapeCore extends Scrape {

	function __construct($pagesPerFile, $db, $region, $map, $fileType, $debug = null) {
		parent::__construct($pagesPerFile, $db, $region, $map, $fileType, $debug);
	}

	/**
     * Sets query for the total number of articles
	 *
	 * @access public
     * @return void
     */
	function setTotalRowsQuery() {
		$this->debug('setTotalRowsQuery <br />');
		$this->debug('map: '.$this->map.' <br />');
		switch($this->map) {
			case 'Noticias':
				$query = "SELECT
					COUNT(M.TB_MATERIA_ID) as totalRows
					FROM TB_MATERIA M
					INNER JOIN TB_SECAO S ON (M.TB_SECAO_ID = S.TB_SECAO_ID)
					INNER JOIN TB_MATERIA_TB_EDITORIA ME ON (M.TB_MATERIA_ID = ME.TB_MATERIA_ID)
					INNER JOIN TB_MATERIA_TB_PRACA MPR ON (M.TB_MATERIA_ID = MPR.TB_MATERIA_ID)
					INNER JOIN TB_EDITORIA E ON (ME.TB_EDITORIA_ID = E.TB_EDITORIA_ID)
					WHERE S.NOME_SECAO = '$this->map'
					AND MPR.TB_PRACA_ID = $this->regionID
					AND E.TITULO = 'Vestibular'
					AND M.CTRL_PUBLICADO = 1
					AND DATA_PUBLICACAO <= NOW()
					AND (DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')
					ORDER BY M.DATA_PUBLICACAO DESC";
				break;
			case 'Artigos':
				$query = "SELECT
					COUNT(M.TB_MATERIA_ID) as totalRows
					FROM TB_MATERIA M
					INNER JOIN TB_SECAO S ON (M.TB_SECAO_ID = S.TB_SECAO_ID)
					INNER JOIN TB_MATERIA_TB_PRACA MPR ON (M.TB_MATERIA_ID = MPR.TB_MATERIA_ID)
					WHERE S.NOME_SECAO = '$this->map'
					AND MPR.TB_PRACA_ID = $this->regionID
					AND M.CTRL_PUBLICADO = 1
					AND DATA_PUBLICACAO <= NOW()
					AND (DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')
					ORDER BY M.DATA_PUBLICACAO DESC";
				break;
			case 'Dicas':
				$query = "SELECT
					COUNT(M.TB_MATERIA_ID) as totalRows
					FROM TB_MATERIA M
					INNER JOIN TB_SECAO S ON (M.TB_SECAO_ID = S.TB_SECAO_ID)
					INNER JOIN TB_MATERIA_TB_PRACA MPR ON (M.TB_MATERIA_ID = MPR.TB_MATERIA_ID)
					WHERE S.NOME_SECAO = '$this->map'
					AND MPR.TB_PRACA_ID = $this->regionID
					AND M.CTRL_PUBLICADO = 1
					AND DATA_PUBLICACAO <= NOW()
					AND (DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')
					ORDER BY M.DATA_PUBLICACAO DESC";
				break;
		}

		$this->debug("$query <br />");
		return $query;
	}





	/**
     * Sets query
	 *
     * @access public
     * @return string
     */
	function setQuery() {
		$this->debug('setQuery <br />');
		switch($this->map) {
			case 'Noticias':
				$query = "SELECT
					M.TB_MATERIA_ID,
					M.TITULO_MATERIA AS title,
					REPLACE(LOWER(M.TITULO_MATERIA), ' ', '-') AS file_name,
					M.SUBTITULO_HOME_SITE,
					M.TEXTO AS article_body,
					DATE_FORMAT(M.DATA_PUBLICACAO,'%d-%b-%Y %T') AS DATA_PUBLICACAO
					FROM TB_MATERIA M
					INNER JOIN TB_SECAO S ON (M.TB_SECAO_ID = S.TB_SECAO_ID)
					INNER JOIN TB_MATERIA_TB_EDITORIA ME ON (M.TB_MATERIA_ID = ME.TB_MATERIA_ID)
					INNER JOIN TB_EDITORIA E ON (ME.TB_EDITORIA_ID = E.TB_EDITORIA_ID)
					INNER JOIN TB_MATERIA_TB_PRACA MPR ON (M.TB_MATERIA_ID = MPR.TB_MATERIA_ID)
					WHERE S.NOME_SECAO = '$this->map'
					AND M.CTRL_PUBLICADO = 1
					AND E.TITULO = 'Vestibular'
					AND MPR.TB_PRACA_ID = $this->regionID
					AND DATA_PUBLICACAO <= NOW()
					AND (DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')
					ORDER BY M.DATA_PUBLICACAO DESC
					limit $this->offset, $this->limit";
				break;
			case 'Artigos':
				$query = "SELECT
					M.TB_MATERIA_ID,
					M.TITULO_MATERIA AS title,
					REPLACE(LOWER(M.TITULO_MATERIA), ' ', '-') AS file_name,
					M.SUBTITULO_HOME_SITE,
					M.TEXTO AS article_body,
					DATE_FORMAT(M.DATA_PUBLICACAO,'%d-%b-%Y %T') AS DATA_PUBLICACAO
					FROM TB_MATERIA M
					INNER JOIN TB_SECAO S ON (M.TB_SECAO_ID = S.TB_SECAO_ID)
					INNER JOIN TB_MATERIA_TB_PRACA MPR ON (M.TB_MATERIA_ID = MPR.TB_MATERIA_ID)
					WHERE S.NOME_SECAO = '$this->map'
					AND MPR.TB_PRACA_ID = $this->regionID
					AND M.CTRL_PUBLICADO = 1
					AND DATA_PUBLICACAO <= NOW()
					AND (DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')
					ORDER BY M.DATA_PUBLICACAO DESC
					limit $this->offset, $this->limit";
				break;
			case 'Dicas':
				$query = "SELECT
					M.TB_MATERIA_ID,
					M.TITULO_MATERIA AS title,
					REPLACE(LOWER(M.TITULO_MATERIA), ' ', '-') AS file_name,
					M.SUBTITULO_HOME_SITE,
					M.TEXTO  AS article_body,
					DATE_FORMAT(M.DATA_PUBLICACAO,'%d-%b-%Y %T') AS DATA_PUBLICACAO
					FROM TB_MATERIA M
					INNER JOIN TB_SECAO S ON (M.TB_SECAO_ID = S.TB_SECAO_ID)
					INNER JOIN TB_MATERIA_TB_PRACA MPR ON (M.TB_MATERIA_ID = MPR.TB_MATERIA_ID)
					WHERE S.NOME_SECAO = '$this->map'
					AND M.CTRL_PUBLICADO = 1
					AND MPR.TB_PRACA_ID = $this->regionID
					AND DATA_PUBLICACAO <= NOW()
					AND (DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')
					ORDER BY M.DATA_PUBLICACAO DESC
					limit $this->offset, $this->limit";
				break;
		}

		$this->debug("$query <br />");
		return $query;
	}


	/**
     * builds the XML file to be created from database queries
	 *
	 * @param array $result
     * @access public
     * @return string
     */
	function setXML($result) {
		$this->debug('<h2>setXML ScrapeCore</h2>');
		$uri = strtolower($this->map)."/".strtolower($this->region);

        $A_contentTag = array('Enem', 'Prouni', 'Vestibular');

		$xml = '<?xml version="1.0" encoding="UTF-8"?>
<DirectoryFeed>
	<WEBSITE>
		<WEBSITE_NAME><![CDATA['.$this->siteUmbrellaName.']]></WEBSITE_NAME>
		<PORTAL_KEY><![CDATA[59600]]></PORTAL_KEY>
		<URLS>
			<URL><![CDATA['.$this->siteURL.']]></URL>
		</URLS>
		<PAGES>'.PHP_EOL;
        $i = 0;
		foreach($result as $row) {
            if ($i > 2) $i = 0;
			$this->debug($row['TB_MATERIA_ID']);
            $this->debug('<h1>file_name: '.$row['file_name'].'</h1>');
            //$this->debug($this->stripAccents("inscições"));
            $fileName = $row['file_name'];
            $fileName = $this->stripAccents($fileName);
            //$fileName = $this->cleanForShortURL(utf8_decode($fileName));
			$fileName = $this->removeSpecialCharacters($fileName);
			$fileName = $this->removeExtraDashes($fileName);
			$fileName = preg_replace('/^-/', '', $fileName);
			$fileName = preg_replace('/-$/', '', $fileName);
            $fileName = preg_replace('/\./', '', $fileName);
			$fileName .= ".html";
            $this->debug("<h1>fileName: $fileName</h1>");

			$old_uri = $this->oldPath . strtolower($this->map) . '_ler.php?id=' . $row['TB_MATERIA_ID'];


            if (strlen($row['article_body']) > 6) {
                $temp_body = $row['article_body'];
                $temp_body = str_replace(array("\r\n", "\r", "\n", "\t"), "", $temp_body);
                $temp_body = nl2br($temp_body);
                $temp_body = strip_tags($temp_body);
                //$temp_body = $this->convertHTML2Text($temp_body, "text");
                //$temp_body = $this->stripAccents($body);
                $temp_body = $this->msword_conversion($temp_body);

                $metaDesc = $this->TruncateString($temp_body, 150);
                //$leader = nl2br($body);
                //$leader = strip_tags($leader);
                $leader = $this->TruncateString($temp_body, 100);


                $article_body = $this->msword_conversion($row['article_body']);
                //$article_body = $this->convertHTML2Text($article_body, "text");
            } else {
                $article_body = '&nbsp;';
                $metaDesc = '&nbsp;';
                $leader = '&nbsp;';
            }
            $this->debug("<h2>leader: $leader</h2>");


            /*
			$body = strip_tags($row['article_body']);
			$body = $this->stripAccents($body);

			$article_body = $this->msword_conversion($row['article_body']);
            $article_body = $this->convertHTML2Text($article_body, "text");

			$metaDesc = $this->TruncateString($body, 150);
            */
			//$metaKey = $this->getMetaKeyword($this->region);
            $metaKey = utf8_decode($this->getMetaKeyword($this->region));

			$isPos = false;
			$isPos = $this->isPos($row['TB_MATERIA_ID']);
			if ($isPos === true) {
				$qualification = "GRADUATE";
			} else {
				$qualification = "UNDERGRADUATE";
			}

            $contentTag = $A_contentTag[$i];
            $i++;



			$xml .= "\t\t\t<PAGE>
				<PAGE_NAME><![CDATA[$fileName]]></PAGE_NAME>
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
					<ARTICLE_TYPE><![CDATA[Standard Article]]></ARTICLE_TYPE>
					<POSTING_DATE><![CDATA[".$row['DATA_PUBLICACAO']."]]></POSTING_DATE>".PHP_EOL;
			$xml .= "\t\t\t\t\t<TITLE><![CDATA[".$row['title']."]]></TITLE>
                    <LEADER><![CDATA[$leader]]></LEADER>
					<FILE_NAME><![CDATA[$fileName]]></FILE_NAME>
					<META_TITLE><![CDATA[".$row['title']." | $this->siteName]]></META_TITLE>
					<META_KEYWORD><![CDATA[".$metaKey."]]></META_KEYWORD>
					<META_DESC><![CDATA[".$metaDesc."]]></META_DESC>
					<SEARCH_ENGINE_META_TAG><![CDATA[INDEX,FOLLOW]]></SEARCH_ENGINE_META_TAG>".PHP_EOL;

            $xml .= "\t\t\t\t\t<LINK_TEXT><![CDATA[".$row['title']."]]></LINK_TEXT>".PHP_EOL;

			$xml .= "\t\t\t\t\t<INHERIT_FLAG><![CDATA[Y]]></INHERIT_FLAG>".PHP_EOL;

			$xml .= "\t\t\t\t\t<BODY><![CDATA[$article_body]]></BODY>".PHP_EOL;




			/* --- taxonomy --- */

			$xml .= "\t\t\t\t\t<SERVICES>
					   <SERVICE PRIMARY='1'><![CDATA[GENERAL_AUDIENCE_EVERYONE]]></SERVICE>
					   <SERVICE><![CDATA[UNIVERSITY_COLLEGE]]></SERVICE>
                    </SERVICES>".PHP_EOL;

            $xml .= "\t\t\t\t\t<SUBJECTS>
                        <SUBJECT PRIMARY='1'>
                            <![CDATA[SUBJECT]]>
                        </SUBJECT>
					</SUBJECTS>".PHP_EOL;

			$xml .= "\t\t\t\t\t<QUALIFICATIONS>
						<QUALIFICATION PRIMARY='1'>
							<![CDATA[$qualification]]>
						</QUALIFICATION>
					</QUALIFICATIONS>".PHP_EOL;

            $xml .= "\t\t\t\t\t<GEOGRAPHIES>
						<GEO
							COUNTRY='BR'
                            STATE='$this->stateInitials'
							PRIMARY='1'/>
					</GEOGRAPHIES>".PHP_EOL;

            /* tags for related articles */
            /* use enem for testing ONLY!!! */
            $xml .= "\t\t\t\t\t<CONTENT_TAGS>
                        <TAG>
                            <TAG_NAME><![CDATA[$contentTag]]></TAG_NAME>
                            <CATEGORY><![CDATA[Education]]></CATEGORY>
                        </TAG>
                    </CONTENT_TAGS>".PHP_EOL;




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

}

?>