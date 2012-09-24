<?php
/**
 * This model is ues to get the SL listing using new libraries.It also contains the wrapper functions to pass the data forward.
 */
require_once ('Platform/Service/ContentEngine.php');
require_once ('Platform/ContentEngine/Page.php');
require_once ('Platform/ContentEngine/ArticleListing.php');
require_once ('Platform/ContentEngine/Directory.php');
require_once ('Platform/Payload/ContentEngine.php');
class sbl_model extends Model{

	/* Declaring Constant related to SBL */
	const CLIENT_TYPE = 'Client';
    const ECL_WEBSITE_NAME = 'WWW.GUIDETOHIGHERED.COM';
    const LOAD_GEO_DATA_KEY_NAME = 'loadGeoData';
    const LOAD_BREADCRUMB_KEY_NAME = 'loadBreadCrumb';
    const PAGE_SIZE_KEYNAME = 'pagesize';
    const CONTENT_REQUEST_TYPE_KEY_NAME = 'contentRequestType';
    const WIDGET_INSTANCE_KEY_NAME = 'widgetInstanceKey';
    const FILTER_DEALER_BY_STATE_KEY_NAME = 'filterDlrByState';
    const WIDGET_REQUEST_TYPE = 'Widget';
    const RADIUS_VALUE = 50;

    private $contentEngineService = NULL; /* to hold the Platform_Service_ContentEngine object */
    private $jsonPage = NULL;   /* to store the Json Response */
    private $page = NULL;	/*To Store the Json Decoded Response */
	private $filterCriteria = array(); /* to store the filter values */
	private $dataValue = array();/* to store the data values  */
	private static $primaryURI;

	public function getPrimaryURI() {
		if(isset($this->primaryURI))
			return $this->primaryURI;
		else
			return NULL;
	}

	public function setPrimaryURI($uri) {
		$this->primaryURI = $uri;
	}
    /**
     * Constructor :
     * */

	function sbl_model() {
		parent::Model();
		$this->load->helper('exceptions_helper');
        $this->load->database();
	}

     /* ---------------- start old Guia database functions ------------------*/

    /*
     * gets total number of coleges
     * by state
	 *
	 * @param int $sitePracaID
     * @access public
     * @return int
     *
     */
    function countTotalColleges($sitePracaID, $xpr) {

        //include('array_helper.php');

        $sqlStr = "SELECT COUNT(DISTINCT(I.TB_INSTITUICAO_ID)) AS CampusCount FROM TB_INSTITUICAO I INNER JOIN TB_INSTITUICAO_UNIDADE U ON (I.TB_INSTITUICAO_ID = U.TB_INSTITUICAO_ID)WHERE U.TB_PRACA_ID = " . $sitePracaID;
        if (strlen(trim($xpr)) > 1) {
            $sqlStr .= " AND I.NOME LIKE '%".$this->db->escape_like_str($xpr)."%' ";
        }
        $query = $this->db->query($sqlStr);
        $row = $query->row_array();
        return $row['CampusCount'];
    }

    /*
     * gets list of colleges
     * by state, using the old Nomad DB
	 *
	 * @param int $strParentId
	 * @param int $regOffset
     * @param int $regPorPagina
     * @access public
     * @return array $arrstrListing
     *
     */
    function fetchCollegeListOldGuia($sitePracaID, $regOffset, $regPorPagina, $xpr) {

        $strSQL = "SELECT COUNT(I.TB_INSTITUICAO_ID) AS CampusCount, I.TB_INSTITUICAO_ID AS ParentID, I.NOME AS ParentSchool, TU.EmailContact as Email
        FROM TB_INSTITUICAO I
        INNER JOIN TB_INSTITUICAO_UNIDADE U ON (I.TB_INSTITUICAO_ID = U.TB_INSTITUICAO_ID)
        LEFT JOIN TB_TOP_UNIVERSITY TU ON (I.TB_INSTITUICAO_ID = TU.UniversidadeID)
        WHERE U.TB_PRACA_ID = $sitePracaID ";

        if (strlen(trim($xpr)) > 1) {
            $strSQL .= " AND I.NOME LIKE '%".$this->db->escape_like_str($xpr)."%' ";
        }


        $strSQL .= " GROUP BY I.TB_INSTITUICAO_ID, I.NOME ORDER BY I.NOME LIMIT $regOffset, $regPorPagina";

        try {
        	//$arrWidget = array();
            $arrstrListing 	= array();
            $arrstrSchoolsData = array();
            $query = $this->db->query($strSQL);
            $arrstrSchoolsData =  $query->result_array();
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        if (is_array($arrstrSchoolsData) && isset($arrstrSchoolsData))
        {
            foreach ( $arrstrSchoolsData as $i => $arrstrSchool )
            {
                if( false == isset( $arrstrSchool['Email'] )) {
					$arrstrSchool['Email'] = '';
				}

                $arrstrListing[$i] = array( 'ParentID'		=> $arrstrSchool['ParentID'],
											'ParentSchool'	=> $arrstrSchool['ParentSchool'],
                                            'Email' 		=> $arrstrSchool['Email'] ,
                                            'CampusCount'	=> $arrstrSchool['CampusCount']
											);
            }
        }

        //$query = $this->db->query($strSQL);
        return $query->result_array();
        //return $this->utf8_array_encode($arrstrListing);

	}

    /*
     * gets list of campuese
     * by college ID, using the old Nomad DB
	 *
	 * @param int $strParentId
	 * @param int $sitePracaID
     * @access public
     * @return array $arrstrData
     *
     */
    function fetchCollegesByParentIdOldGuia($strParentId, $sitePracaID)
    {
        $setSQL = "SET @@group_concat_max_len = 9999999;";
        $query = $this->db->query($setSQL);
        $strSQL = "SELECT U.TB_UNIDADE_ID, U.NOME AS InstitutionName, U.ENDERECO AS Address, U.BAIRRO as Neighborhood, U.CIDADE AS City, U.UF AS State, U.FONE AS PhoneNumber, U.FAX, TU.EmailContact AS Email, U.SITE, I.TB_INSTITUICAO_ID AS ParentID, I.NOME AS ParentSchool, I.TIPO AS PublicOrPrivate,

(SELECT GROUP_CONCAT(CONCAT_WS('=>',C.TB_CURSO_GRADUACAO_ID, C.NOME) SEPARATOR '_|_')
FROM TB_CURSO_GRADUACAO C
INNER JOIN TB_CURSO_GRADUACAO_TB_UNIDADE GU ON (C.TB_CURSO_GRADUACAO_ID = GU.TB_CURSO_GRADUACAO_ID)
WHERE GU.TB_UNIDADE_ID = U.TB_UNIDADE_ID
AND U.TB_PRACA_ID = $sitePracaID
ORDER BY C.NOME) AS Cursos

FROM TB_INSTITUICAO_UNIDADE U
INNER JOIN TB_INSTITUICAO I ON (I.TB_INSTITUICAO_ID = U.TB_INSTITUICAO_ID)
LEFT JOIN TB_TOP_UNIVERSITY TU ON (I.TB_INSTITUICAO_ID = TU.UniversidadeID)
WHERE U.TB_INSTITUICAO_ID = $strParentId
GROUP BY U.TB_INSTITUICAO_ID, U.NOME
ORDER BY U.NOME";

        try {
        	$arrstrData = array();
            $arrstrListing 	= array();
            $arrstrSchoolsData = array();
            $query = $this->db->query($strSQL);
            $arrstrSchoolsData =  $query->result_array();
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        if (is_array($arrstrSchoolsData) && isset($arrstrSchoolsData))
        {
            foreach ( $arrstrSchoolsData as $i => $arrstrSchool )
            {
                if( false == isset( $arrstrSchool['Email'] )) {
					$arrstrSchool['Email'] = '';
				}

                $arrstrListing[$i] = array( 'InstitutionName' 	=> $arrstrSchool['InstitutionName'] ,
	    									'Address' 			=> $arrstrSchool['Address'] ,
                                            'Neighborhood' 		=> $arrstrSchool['Neighborhood'] ,
	    									'City'				=> $arrstrSchool['City'] ,
                                            'State' 			=> $arrstrSchool['State'] ,
											'Country' 			=> "BR" ,
											'PhoneNumber' 		=> $arrstrSchool['PhoneNumber'] ,
                                            'Email' 		    => $arrstrSchool['Email'] ,
											'PublicOrPrivate'	=> $arrstrSchool['PublicOrPrivate'] ,
											'InstitutionType'   => "" ,
                                            //'InstitutionType'   => $arrstrSchool['InstitutionType'] ,
											'ParentSchool' 		=> $arrstrSchool['ParentSchool'],
											'ParentID'			=> $arrstrSchool['ParentID'],
											//'Ranking'			=> $arrstrSchool['Ranking'],
											//'TaxonomyCode'		=> $arrstrSchool['TaxonomyCode'],
											//'TaxonomyName'		=> $arrstrSchool['TaxonomyName'],
											//'CampusCount'	=> $arrstrSchool['CampusCount']
                                            'Cursos'            => $arrstrSchool['Cursos']
											);
            }
        }


		$arrstrData['schools'] 			= $arrstrListing;
		$arrstrData['total_count']		= sizeof($arrstrListing);

		return $arrstrData;
        //return $this->utf8_array_encode($arrstrData);

    }

    /*
     * gets total number of courses
     * offered in a state
	 *
	 * @param int $sitePracaID
     * @access public
     * @return int
     *
     */
    function countTotalCourses($pracaID, $xpr) {

        $sqlStr = "SELECT COUNT(DISTINCT(C.TB_CURSO_GRADUACAO_ID)) AS CoursesCount
FROM TB_CURSO_GRADUACAO C
INNER JOIN TB_CURSO_GRADUACAO_TB_UNIDADE CU ON ( C.TB_CURSO_GRADUACAO_ID = CU.TB_CURSO_GRADUACAO_ID )
INNER JOIN TB_INSTITUICAO_UNIDADE U ON ( CU.TB_UNIDADE_ID = U.TB_UNIDADE_ID )
WHERE U.TB_PRACA_ID = $pracaID";
        if (strlen(trim($xpr)) > 1) {
            $sqlStr .= " AND C.NOME LIKE '%".$this->db->escape_like_str($xpr)."%' ";
        }

        $query = $this->db->query($sqlStr);
        $row = $query->row_array();
        return $row['CoursesCount'];
    }

    /*
     * gets list of courses
     * and number of campuses that offer it
     * by ID, using the old Nomad DB
	 *
	 * @param int $sitePracaID
	 * @param int $regOffset
     * @param int $regPorPagina
     * @access public
     * @return array $arrstrData
     *
     */
    function fetchCoursesByPageNumberOldGuia($sitePracaID, $regOffset, $regPorPagina, $xpr) {

        $strSQL = "SELECT C.TB_CURSO_GRADUACAO_ID as CourseID, C.NOME as CourseName, COUNT(C.TB_CURSO_GRADUACAO_ID) AS OfferredInTotal
FROM TB_CURSO_GRADUACAO C
INNER JOIN TB_CURSO_GRADUACAO_TB_UNIDADE CU ON ( C.TB_CURSO_GRADUACAO_ID = CU.TB_CURSO_GRADUACAO_ID )
INNER JOIN TB_INSTITUICAO_UNIDADE U ON ( CU.TB_UNIDADE_ID = U.TB_UNIDADE_ID )
WHERE U.TB_PRACA_ID = $sitePracaID ";

        if (strlen(trim($xpr)) > 1) {
            $strSQL .= " AND C.NOME LIKE '%".$this->db->escape_like_str($xpr)."%' ";
        }

        $strSQL .= "GROUP BY 1, 2 ORDER BY 2 LIMIT $regOffset, $regPorPagina";


        try {
            $arrstrData = array();
            $arrstrListing 	= array();
            $arrstrCoursesData = array();
            $query = $this->db->query($strSQL);
            $arrstrCoursesData =  $query->result_array();
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        if (is_array($arrstrCoursesData) && isset($arrstrCoursesData))
        {
            foreach ( $arrstrCoursesData as $i => $arrstrCourse )
            {
                $arrstrListing[$i] = array( 'CourseID'          => $arrstrCourse['CourseID'] ,
	    									'CourseName' 		=> $arrstrCourse['CourseName'] ,
                                            'OfferredInTotal' 	=> $arrstrCourse['OfferredInTotal']
											);


            }
        }


		$arrstrData['courses'] 			= $arrstrListing;
		$arrstrData['total_count']		= sizeof($arrstrListing);

		return $arrstrData;
        //return $this->utf8_array_encode($arrstrData);
	}

    /*
     * searches course info by name
     * and list of campuses that offer it
     * by ID, using the old Nomad DB
	 *
	 * @param int $courseId
	 * @param int $sitePracaID
     * @access public
     * @return array $arrstrData
     *
     */
    function fetchCoursesByIdOldGuiaByName($courseName, $sitePracaID)
    {
        $arrstrData = array();

        // get info about curso
        $strSQL = "SELECT TB_CURSO_GRADUACAO_ID as CourseID, NOME as title, DESCRICAO as body
        FROM TB_CURSO_GRADUACAO
        WHERE NOME = '$courseName'";
        $query = $this->db->query($strSQL);
        $courseInfo = $query->row_array();
        //$arrstrData['courseInfo'] = $this->utf8_array_encode($courseInfo);
        $arrstrData['courseInfo'] = $courseInfo;

        // get list of campueses that offer this course
        $strSQL = "SELECT C.NOME AS Curso, I.TB_INSTITUICAO_ID AS ID, I.NOME AS NAME, COUNT(IU.TB_UNIDADE_ID) AS TOTAL
        FROM TB_INSTITUICAO I
        INNER JOIN TB_INSTITUICAO_UNIDADE IU ON (I.TB_INSTITUICAO_ID = IU.TB_INSTITUICAO_ID)
        INNER JOIN TB_CURSO_GRADUACAO_TB_UNIDADE CU ON (IU.TB_UNIDADE_ID = CU.TB_UNIDADE_ID)
        INNER JOIN TB_CURSO_GRADUACAO C ON (C.TB_CURSO_GRADUACAO_ID = CU.TB_CURSO_GRADUACAO_ID)
        WHERE IU.TB_PRACA_ID = $sitePracaID
        AND C.NOME = '$courseName'
        GROUP BY 1, 2 ORDER BY I.NOME";

        //$schoolsList = $query->row_array();
        try {
            $arrstrListing 	= array();
            $arrstrSchoolData = array();
            $query = $this->db->query($strSQL);
            $arrstrSchoolData =  $query->result_array();
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        if (is_array($arrstrSchoolData) && isset($arrstrSchoolData))
        {
            foreach ( $arrstrSchoolData as $i => $arrstrSchool )
            {
                $arrstrListing[$i] = array( 'SchoolID'          => $arrstrSchool['ID'] ,
	    									'SchoolName'        => $arrstrSchool['NAME'] ,
                                            'CampusCount'       => $arrstrSchool['TOTAL']
											);


            }
        }
        $arrstrData['schools'] = $arrstrListing;
        //$arrstrData['schools'] = $schoolsList;

        $arrstrData['total_count']		= sizeof($arrstrListing);

        //return $this->utf8_array_encode($arrstrData);
        return $arrstrData;
    }
    /*
     * searches course info by id
     * and list of campuses that offer it
     * by ID, using the old Nomad DB
	 *
	 * @param int $courseId
	 * @param int $sitePracaID
     * @access public
     * @return array $arrstrData
     *
     */
    function fetchCoursesByIdOldGuia($courseId, $sitePracaID)
    {
        $arrstrData = array();

        // get info about curso
        $strSQL = "SELECT TB_CURSO_GRADUACAO_ID as CourseID, NOME as title, DESCRICAO as body FROM TB_CURSO_GRADUACAO WHERE TB_CURSO_GRADUACAO_ID = $courseId";
        $query = $this->db->query($strSQL);
        $courseInfo = $query->row_array();
        //$arrstrData['courseInfo'] = $this->utf8_array_encode($courseInfo);
        $arrstrData['courseInfo'] = $courseInfo;

        // get list of campueses that offer this course
        $strSQL = "SELECT I.TB_INSTITUICAO_ID as ID, I.NOME as NAME, COUNT(IU.TB_UNIDADE_ID) AS TOTAL
FROM TB_INSTITUICAO I
INNER JOIN TB_INSTITUICAO_UNIDADE IU ON (I.TB_INSTITUICAO_ID = IU.TB_INSTITUICAO_ID)
INNER JOIN TB_CURSO_GRADUACAO_TB_UNIDADE CU ON (IU.TB_UNIDADE_ID = CU.TB_UNIDADE_ID)
WHERE IU.TB_PRACA_ID = $sitePracaID AND CU.TB_CURSO_GRADUACAO_ID = $courseId
GROUP BY 1, 2 ORDER BY I.NOME";

        //$schoolsList = $query->row_array();
        try {
            $arrstrListing 	= array();
            $arrstrSchoolData = array();
            $query = $this->db->query($strSQL);
            $arrstrSchoolData =  $query->result_array();
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        if (is_array($arrstrSchoolData) && isset($arrstrSchoolData))
        {
            foreach ( $arrstrSchoolData as $i => $arrstrSchool )
            {
                $arrstrListing[$i] = array( 'SchoolID'          => $arrstrSchool['ID'] ,
	    									'SchoolName'        => $arrstrSchool['NAME'] ,
                                            'CampusCount'       => $arrstrSchool['TOTAL']
											);


            }
        }
        $arrstrData['schools'] = $arrstrListing;
        //$arrstrData['schools'] = $schoolsList;

        $arrstrData['total_count']		= sizeof($arrstrListing);

        //return $this->utf8_array_encode($arrstrData);
        return $arrstrData;
    }

    function numPaginas($total, $limite) {
		if ($total <= $limite) return 1;
		else {
			$resto = ($total%$limite);

			if ($resto > 0) {
				return (floor($total/$limite) + 1);
			} else
				return floor($total/$limite);
		}
	}

	function getMySQLOffset($pagina, $total, $limite) {
		if ($pagina == 0) return 1;
		else return ($pagina * $limite - $limite);
	}

	function getRegistroInicial($pagina, $total, $limite) {
		if ($total < 1) return 0;
		else return $this -> getMySQLOffset($pagina, $total, $limite) + 1;
	}

	function getRegistroFinal($pagina, $total, $limite) {
		$registro = (($pagina + 1) * $limite) - $limite;
		if ($registro > $total)
			return $total;
		else
			return $registro;
	}

    /* ---------------- end old Guia database functions ------------------*/

	/***************************
	 **** All Fetch Request ****
	 ***************************/

	function fetchWidget ($strDirectoryName, $widgetName, $intMaxCount = NULL) {
        $this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);
    	$payload = array(
			'filterCriteria'=>array(
			    	Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME 				=> CE_SITENAME,
			    	Platform_Payload_ContentEngine::DIRECTORY_PATH_KEY_NAME 			=> $strDirectoryName,
			    	Platform_Payload_ContentEngine::RECURSIVE_SEARCH_ENABLED_KEY_NAME 	=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::LOAD_ABSTRACT_KEY_NAME 				=> Platform_Payload_ContentEngine::VALUE_TRUE,
                    Platform_Payload_ContentEngine::WEBPAGE_URI_KEY_NAME                =>  "$strDirectoryName",
            ),

			'sortCriteria' => array(
    				Platform_Payload_ContentEngine::ARTICLE_SORT_BY_POSTDATE_KEY_NAME => Platform_Payload_ContentEngine::SORT_DESC,
    				));
    	if( $intMaxCount != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = $intMaxCount;
    	} else {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = 10;
    	}

        try {
        	$arrWidget = array();
			$jsonResponse 		= $this->contentEngineService->getPageContent($payload);
            $pageObject = Platform_ContentEngine_Page::jsonToPage($jsonResponse);
            $arrWidget = $pageObject->getPageElementsByName($widgetName);
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        if (is_array($arrWidget) && isset($arrWidget))
        {
            foreach($arrWidget as $widget)
            {
                $widgetListing = array();
                if( $widget->articles )
                {
                    $arrstrData = array();
                    foreach ($widget->articles as $article)
                    {
                        $widgetListing[] = array(
                            'title'             =>  $article->title ,
	    					'leader' 			=>  $article->leader ,
                            'uri' 				=>  str_replace('/'.CE_DIRECTORY_NAME.'/', '/', $article->uri),
	    					'postDateTimeStamp' =>  $article->postDate->timestamp,
	    					'images'			=>  $article->images,
                            'linkText'          =>  $article->linkText,
                            'articleKey'        =>  $article->articleKey,
                            );
                    }
                    $arrstrData['widget_listing'] 	= $widgetListing;
                    $arrstrData['total_count'] 	 	= $widget->totalCount;
                    return $arrstrData;
                }
                else
                {
                    return NULL;
                }
            }
        }
	}

	function fetchArticlesByDirectoryName( $strDirectoryName, $intMaxCount = NULL ) {
		$this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);
    	$payload = array(
			'filterCriteria'=>array(
			    	Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME 				=> CE_SITENAME,
			    	Platform_Payload_ContentEngine::DIRECTORY_PATH_KEY_NAME 			=> $strDirectoryName,
			    	Platform_Payload_ContentEngine::RECURSIVE_SEARCH_ENABLED_KEY_NAME 	=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::LOAD_ABSTRACT_KEY_NAME 				=> Platform_Payload_ContentEngine::VALUE_TRUE,),
			'sortCriteria' => array(
    				Platform_Payload_ContentEngine::ARTICLE_SORT_BY_POSTDATE_KEY_NAME => Platform_Payload_ContentEngine::SORT_DESC,
                    ));
    	if( $intMaxCount != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = $intMaxCount;
    	} else {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = 10;
    	}
    	/*if($pageNumber != NULL && $perPageLimit != NULL) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_NUMBER_KEYNAME] = $pageNumber;
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_SIZE_KEYNAME] = $perPageLimit;
    	}*/

        try {
        	$articleListingData = array();
			$jsonResponse 		= $this->contentEngineService->getArticlePages($payload);
        	$articleListingData = Platform_ContentEngine_ArticleListing::jsonToArticleListing($jsonResponse);
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        $arrstrData = array();
        if( isset($articleListingData) && $articleListingData->articles ) {

        	$arrstrListing = array();
	    	foreach ( $articleListingData->articles as $value) {
	    		$arrstrListing[] = array(	'title' 			=> $value->title ,
	    									'leader' 			=> $value->leader ,
                                            'articleKey'        => $value->articleKey,
	    									'uri' 				=> str_replace('/'.CE_DIRECTORY_NAME.'/', '/', $value->uri),
	    									'postDateTimeStamp' => $value->postDate->timestamp,
	    									'images'			=> $value->images );
	    	}
	    	$arrstrData['article_listing'] 	= $arrstrListing;
	    	$arrstrData['total_count'] 	 	= $articleListingData->totalCount;
	    	return $arrstrData;
		}else {
		    return NULL;
		}
	}

	function fetchArticlesByDirectoryNameByPageNumber( $strDirectoryName, $intPageNumber = NULL, $intMaxCount = NULL, $strOrderBy = 'POSTDATE' ) {

		$this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);
    	$payload = array(
			'filterCriteria'=>array(
			    	Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME 				=> CE_SITENAME,
			    	Platform_Payload_ContentEngine::DIRECTORY_PATH_KEY_NAME 			=> $strDirectoryName,
			    	Platform_Payload_ContentEngine::RECURSIVE_SEARCH_ENABLED_KEY_NAME 	=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::LOAD_ABSTRACT_KEY_NAME 				=> Platform_Payload_ContentEngine::VALUE_TRUE)
					);
    	if( 'POSTDATE' == $strOrderBy ) {
    		$payload['sortCriteria'][Platform_Payload_ContentEngine::ARTICLE_SORT_BY_POSTDATE_KEY_NAME] = Platform_Payload_ContentEngine::SORT_DESC;
    	}
    	if( $intMaxCount != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = $intMaxCount;
    	} else {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = 10;
    	}
    	/*if($pageNumber != NULL && $perPageLimit != NULL) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_NUMBER_KEYNAME] = $pageNumber;
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_SIZE_KEYNAME] = $perPageLimit;
    	}*/

		if( $intPageNumber != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_NUMBER_KEYNAME] = $intPageNumber;
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_SIZE_KEYNAME] 	= $intMaxCount;
    	}

        try {
        	$articleListingData = array();
			$jsonResponse 		= $this->contentEngineService->getArticlePages($payload);
        	$articleListingData = Platform_ContentEngine_ArticleListing::jsonToArticleListing($jsonResponse);
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        $arrstrData = array();
        $arrstrData['article_listing'] 	= NULL;
	    $arrstrData['total_count'] 		= NULL;
        if( false == is_null( $articleListingData ) && true == is_array( $articleListingData->articles ) && 0 < sizeof( $articleListingData->articles )) {
	        if( $articleListingData->articles ) {
	        	$arrstrListing = array();
		    	foreach ( $articleListingData->articles as $value) {
		    		$value->uri = str_replace('/pos-graduacao/', '/posgraduacao/', $value->uri);
		    		$arrstrListing[] = array(	'title' 			=> $value->title ,
		    									'leader' 			=> $value->leader ,
                                                'articleKey' 		=> $value->articleKey ,
                                                'abstract' 			=> $value->abstract ,
		    									'uri' 				=> str_replace('/'.CE_DIRECTORY_NAME.'/', '/', $value->uri),
		    									'postDateTimeStamp' => $value->postDate->timestamp,
		    									'images'			=> $value->images,
		    									'publicationSource' => $value->publicationSource );
		    	}
		    	$arrstrData['article_listing'] 	= $arrstrListing;
		    	$arrstrData['total_count'] 	 	= $articleListingData->totalCount;
	        }
        }
        return $arrstrData;
	}

	function fetchArticlesByDirectoryNameByTagsByPageNumber( $strDirectoryName, $arrintTags, $intPageNumber = NULL, $intMaxCount = NULL, $strOrderBy = 'POSTDATE' ) {

		// $strTags = implode( ',', $arrintTags );

		$this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);
    	$payload = array(
			'filterCriteria'=>array(
			    	Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME 				=> CE_SITENAME,
			    	Platform_Payload_ContentEngine::DIRECTORY_PATH_KEY_NAME 			=> $strDirectoryName,
			    	Platform_Payload_ContentEngine::RECURSIVE_SEARCH_ENABLED_KEY_NAME 	=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::LOAD_ABSTRACT_KEY_NAME 				=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::TAG_KEYS_KEY_NAME      				=> $arrintTags,
			    	Platform_Payload_ContentEngine::LOAD_TAGS_KEY_NAME      			=> Platform_Payload_ContentEngine::VALUE_TRUE,
                )
					);
    	if( 'POSTDATE' == $strOrderBy ) {
    		$payload['sortCriteria'][Platform_Payload_ContentEngine::ARTICLE_SORT_BY_POSTDATE_KEY_NAME] = Platform_Payload_ContentEngine::SORT_DESC;
    	}
    	if( $intMaxCount != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = $intMaxCount;
    	} else {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = 10;
    	}
    	/*if($pageNumber != NULL && $perPageLimit != NULL) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_NUMBER_KEYNAME] = $pageNumber;
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_SIZE_KEYNAME] = $perPageLimit;
    	}*/

		if( $intPageNumber != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_NUMBER_KEYNAME] = $intPageNumber;
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_SIZE_KEYNAME] 	= $intMaxCount;
    	}

        try {
        	$articleListingData = array();
			$jsonResponse 		= $this->contentEngineService->getArticlePages($payload);
        	$articleListingData = Platform_ContentEngine_ArticleListing::jsonToArticleListing($jsonResponse);
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        $arrstrData = array();
        $arrstrData['article_listing'] 	= NULL;
		$arrstrData['total_count'] 	 	= NULL;
        if( false == is_null( $articleListingData ) && true == is_array( $articleListingData->articles ) && 0 < sizeof( $articleListingData->articles )) {
        	$arrstrListing = array();
	    	foreach ( $articleListingData->articles as $value) {

	    		$arrstrListing[] = array(	'title' 			=> $value->title ,
	    									'leader' 			=> $value->leader ,
	    									'uri' 				=> str_replace('/'.CE_DIRECTORY_NAME.'/', '/', $value->uri),
	    									'postDateTimeStamp' => $value->postDate->timestamp,
	    									'images'			=> $value->images,
	    									'publicationSource' => $value->publicationSource );
	    	}
	    	$arrstrData['article_listing'] 	= $arrstrListing;
	    	$arrstrData['total_count'] 	 	= $articleListingData->totalCount;
		}

		return $arrstrData;

	}

	function fetchArticlesByDirectoryNameByQualCodeByPageNumber( $strDirectoryName, $strQualCode, $intPageNumber = NULL, $intMaxCount = NULL, $strOrderBy = 'POSTDATE' ) {

		$this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);
    	$payload = array(
			'filterCriteria'=>array(
			    	Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME 				=> CE_SITENAME,
			    	Platform_Payload_ContentEngine::DIRECTORY_PATH_KEY_NAME 			=> $strDirectoryName,
			    	Platform_Payload_ContentEngine::RECURSIVE_SEARCH_ENABLED_KEY_NAME 	=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::LOAD_ABSTRACT_KEY_NAME 				=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	'qualCode' => $strQualCode )
					);
    	if( 'POSTDATE' == $strOrderBy ) {
    		$payload['sortCriteria'][Platform_Payload_ContentEngine::ARTICLE_SORT_BY_POSTDATE_KEY_NAME] = Platform_Payload_ContentEngine::SORT_DESC;
    	}
    	if( $intMaxCount != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = $intMaxCount;
    	} else {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = 10;
    	}
    	/*if($pageNumber != NULL && $perPageLimit != NULL) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_NUMBER_KEYNAME] = $pageNumber;
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_SIZE_KEYNAME] = $perPageLimit;
    	}*/

		if( $intPageNumber != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_NUMBER_KEYNAME] = $intPageNumber;
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_SIZE_KEYNAME] 	= $intMaxCount;
    	}

        try {
        	$articleListingData = array();
			$jsonResponse 		= $this->contentEngineService->getArticlePages($payload);
        	$articleListingData = Platform_ContentEngine_ArticleListing::jsonToArticleListing($jsonResponse);
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        $arrstrData = array();
        $arrstrData['article_listing'] 	= NULL;
		$arrstrData['total_count'] 	 	= NULL;
        if( false == is_null( $articleListingData ) && true == is_array( $articleListingData->articles ) && 0 < sizeof( $articleListingData->articles )) {
        	$arrstrListing = array();
	    	foreach ( $articleListingData->articles as $value) {

	    		$arrstrListing[] = array(	'title' 			=> $value->title ,
	    									'leader' 			=> $value->leader ,
	    									'uri' 				=> str_replace('/'.CE_DIRECTORY_NAME.'/', '/', $value->uri),
	    									'postDateTimeStamp' => $value->postDate->timestamp,
	    									'images'			=> $value->images,
	    									'publicationSource' => $value->publicationSource );
	    	}
	    	$arrstrData['article_listing'] 	= $arrstrListing;
	    	$arrstrData['total_count'] 	 	= $articleListingData->totalCount;
		}

		return $arrstrData;

	}

	function fetchArticlesByDirectoryNameByTags( $strDirectoryName, $arrintTags, $intMaxCount = 10, $strOrderBy = 'POSTDATE' ) {

		$this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);
    	$payload = array(
			'filterCriteria'=>array(
			    	Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME 				=> CE_SITENAME,
			    	Platform_Payload_ContentEngine::DIRECTORY_PATH_KEY_NAME 			=> $strDirectoryName,
			    	Platform_Payload_ContentEngine::RECURSIVE_SEARCH_ENABLED_KEY_NAME 	=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::LOAD_ABSTRACT_KEY_NAME 				=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::TAG_KEYS_KEY_NAME      				=> $arrintTags,
			    	Platform_Payload_ContentEngine::LOAD_TAGS_KEY_NAME      			=> Platform_Payload_ContentEngine::VALUE_TRUE)
					);

    	if( 'POSTDATE' == $strOrderBy ) {
    		$payload['sortCriteria'][Platform_Payload_ContentEngine::ARTICLE_SORT_BY_POSTDATE_KEY_NAME] = Platform_Payload_ContentEngine::SORT_DESC;
    	}
    	if( $intMaxCount != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = $intMaxCount;
    	} else {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = 10;
    	}

        try {
        	$articleListingData = array();
			$jsonResponse 		= $this->contentEngineService->getArticlePages($payload);
        	$articleListingData = Platform_ContentEngine_ArticleListing::jsonToArticleListing($jsonResponse);
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        $arrstrData = array();
        $arrstrData['article_listing'] 	= NULL;
	    $arrstrData['total_count'] 		= NULL;

        if( false == is_null( $articleListingData ) && true == is_array( $articleListingData->articles ) && 0 < sizeof( $articleListingData->articles )) {
        	$arrstrListing = array();
	    	foreach ( $articleListingData->articles as $value) {
	    		$arrstrListing[] = array(	'title' 			=> $value->title ,
	    									'leader' 			=> $value->leader ,
	    									'uri' 				=> str_replace('/'.CE_DIRECTORY_NAME.'/', '/', $value->uri),
	    									'postDateTimeStamp' => $value->postDate->timestamp,
	    									'images'			=> $value->images,
	    									'publicationSource' => $value->publicationSource );
	    	}
	    	$arrstrData['article_listing'] 	= $arrstrListing;
	    	$arrstrData['total_count'] 	 	= $articleListingData->totalCount;
		}
		return $arrstrData;
	}


	function fetchArticlesByDirectoryNameByQualCode( $strDirectoryName, $strQualCode, $intMaxCount = 10, $strOrderBy = 'POSTDATE' ) {

		$this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);

		$payload = array(
			'filterCriteria'=>array(
			    	Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME 				=> CE_SITENAME,
			    	Platform_Payload_ContentEngine::DIRECTORY_PATH_KEY_NAME 			=> $strDirectoryName,
			    	Platform_Payload_ContentEngine::RECURSIVE_SEARCH_ENABLED_KEY_NAME 	=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::LOAD_ABSTRACT_KEY_NAME 				=> Platform_Payload_ContentEngine::VALUE_TRUE,
			    	'qualCode' => $strQualCode )
					);

    	if( 'POSTDATE' == $strOrderBy ) {
    		$payload['sortCriteria'][Platform_Payload_ContentEngine::ARTICLE_SORT_BY_POSTDATE_KEY_NAME] = Platform_Payload_ContentEngine::SORT_DESC;
    	}
    	if( $intMaxCount != NULL ) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = $intMaxCount;
    	} else {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = 10;
    	}

        try {
        	$articleListingData = array();
			$jsonResponse 		= $this->contentEngineService->getArticlePages($payload);
        	$articleListingData = Platform_ContentEngine_ArticleListing::jsonToArticleListing($jsonResponse);
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }

        $arrstrData = array();
        $arrstrData['article_listing'] 	= NULL;
	    $arrstrData['total_count'] 		= NULL;

        if( false == is_null( $articleListingData ) && true == is_array( $articleListingData->articles ) && 0 < sizeof( $articleListingData->articles )) {
        	$arrstrListing = array();
	    	foreach ( $articleListingData->articles as $value) {
	    		$arrstrListing[] = array(	'title' 			=> $value->title ,
	    									'leader' 			=> $value->leader ,
	    									'uri' 				=> str_replace('/'.CE_DIRECTORY_NAME.'/', '/', $value->uri),
	    									'postDateTimeStamp' => $value->postDate->timestamp,
	    									'images'			=> $value->images,
	    									'publicationSource' => $value->publicationSource );
	    	}
	    	$arrstrData['article_listing'] 	= $arrstrListing;
	    	$arrstrData['total_count'] 	 	= $articleListingData->totalCount;
		}
		return $arrstrData;
	}

	//function fetchCollegesByCollegeNameByStateCodeByCountryCodeByPageNumber( $strCollegeName = NULL, $strStateCode = 'MG', $strCountryCode = 'BR', $intPageNumber = NULL, $intMaxCount = NULL, $strOrderBy = 'POSTDATE' ) {
    function fetchCollegesByCollegeNameByStateCodeByCountryCodeByPageNumber( $strCollegeName = NULL, $strStateCode = 'MG', $strCountryCode = 'BR', $intOffset = NULL, $intMaxCount = NULL, $strOrderBy = 'POSTDATE' ) {

        include('array_helper.php');
		include_once(COMMON_EDUCATION_PATH.'libraries/ipeds.php');
		$ipeds = new ipeds(array('ipeds_env'=>'dev'));

		if( true == is_null( $intMaxCount )) {
			$intMaxCount = PAGE_SIZE;
		}

        $arrstrSearch = array();
		if( false == is_null( $strCollegeName ) && 0 < strlen( $strCollegeName )) {
			//$arrstrSearch['name'] = $strCollegeName;
			$arrstrSearch['parent_school'] = $strCollegeName;
		}

		$arrstrSearch['state'] 		= $strStateCode;
		$arrstrSearch['country'] 	= $strCountryCode;

        //$arrstrSchoolsData = $ipeds->search_schools( $arrstrSearch, array('group_by_field'=>'ParentID', 'sort'=>'ParentSchool', 'offset'=>$intOffset, 'limit'=>$intMaxCount));
        // group by meta fields
        $arrstrSchoolsData = $ipeds->search_schools($arrstrSearch,array('limit'=>'200', 'offset'=>$intOffset,'group_by_field'=>'ParentID','sort'=>'ParentSchool','sort_direction' => 'asc','group_by_meta_fields'=>array('ParentSchool')));


        //$arrstrSchoolsData = $this->utf8_array_encode( $arrstrSchoolsData );
		$arrstrSchoolsData['schools'] = $this->utf8_array_encode( $arrstrSchoolsData['schools'] );

        // limit the number of schools returned
        $arrstrSchoolsData['schools'] = array_slice($arrstrSchoolsData['schools'], 0, $intMaxCount);
        $numOfSchools = sizeof($arrstrSchoolsData['schools']);

		$arrstrData 	= array();
		$arrstrListing 	= array();

		if( $arrstrSchoolsData['schools'] ) {
			$i=0;
            $numOfCampus = 0;
			foreach ( $arrstrSchoolsData['schools'] as $arrstrSchool ) {
                $totalCampusNum = sizeof($arrstrSchool) - 2;
                $numOfCampus += $totalCampusNum;

				if( false == isset( $arrstrSchool[0]['Email'] )) {
					$arrstrSchool[0]['Email'] = '';
				}
				$arrstrListing[$i] = array( 'ParentID'		=> $arrstrSchool[0]['ParentID'],
											'ParentSchool'	=> $arrstrSchool[0]['ParentSchool'],
											//'Ranking'		=> $arrstrSchool[0]['Ranking'],
                                            'Email' 		=> $arrstrSchool[0]['Email'] ,
                                            'CampusCount'	=> $totalCampusNum
											//'CampusCount'	=> ( count( $arrstrSchool ) - 1 )
											);
				$i++;
			}
		}
		$arrstrData['schools'] 			= $arrstrListing;
        $arrstrData['nextOffset'] 	    = $numOfCampus;
        $arrstrData['currOffset'] 		= $intOffset;
		$arrstrData['total_count']		= $arrstrSchoolsData['school_count'];

		return $arrstrData;
	}

	function fetchCoursesByParentSchool( $limit = 5000 ) {

        $A_cursosAll = array();
        $A_cursosList = array();
        $A_numSchools = array();
        $A_parentID = array();
        $arrstrData = array();

		include('array_helper.php');
		include_once(COMMON_EDUCATION_PATH.'libraries/ipeds.php');
		$ipeds = new ipeds(array('ipeds_env'=>'dev'));

        $schools = $ipeds->search_schools(array('country'=>'BR','state'=>STATEABBREV),array('fields'=>'ParentSchool,ParentID,TaxonomyCode,TaxonomyName','limit'=>$limit, 'sort'=>'ParentSchool','sort_direction' => 'asc'));

        foreach ($schools['schools'] as $k=>$v) {
            // add parent school ids to array
            // and set its value as another array
            if (!array_key_exists(trim($v['ParentID']), $A_parentID)) {
                $A_parentID[trim($v['ParentID'])] = array();
            }

            // add taxonomy names to parent school array
            // ONLY ONCE
            foreach(array_unique($v['TaxonomyName']) as $tn) {
                if (!in_array(trim($tn), $A_parentID[trim($v['ParentID'])])) {
                    array_push($A_parentID[trim($v['ParentID'])], trim($tn));
                }
            }
        }

        // add each taxonomy name from each parent school
        // to array with all courses
        foreach ($A_parentID as $k=>$v) {
            foreach(($v) as $tn) {
                array_push($A_cursosAll, trim($tn));
            }
        }

        $A_cursosAll = $this->utf8_array_encode( $A_cursosAll );

        // number of schools that offer a course
        $A_numSchools = array_count_values($A_cursosAll);
        ksort($A_numSchools);

        //list of unique courses
        $A_cursosList = array_unique($A_cursosAll);

        //sort list of courses by values
        asort($A_cursosList);

        $arrstrData['cursosList'] = $A_cursosList;
        $arrstrData['numSchools'] = $A_numSchools;

		return $arrstrData;

	}

	function fetchCollegesByCourses( $strCourses ) {
        /* comment this out on dev/prod
        if(mb_detect_encoding($strCourses,'UTF-8')) {
            $strCourses = utf8_decode($strCourses);
        }
        */
        include('array_helper.php');
		include_once(COMMON_EDUCATION_PATH.'libraries/ipeds.php');
		$ipeds = new ipeds(array('ipeds_env'=>'dev'));

		if( false == is_null( $strCourses ) && 0 < strlen( $strCourses )) {
			$arrstrSearch['taxonomy_codes'] = mb_strtoupper($strCourses,  'UTF-8');
            //$arrstrSearch['taxonomy_names'] = mb_strtoupper($strCourses,  'UTF-8');
		}

        $arrstrSearch['state'] 		= STATEABBREV;
		$arrstrSearch['country'] 	= 'BR';


        $arrstrSchoolsData = $ipeds->search_schools( $arrstrSearch, array('group_by_field'=>'ParentID', 'sort'=>'ParentSchool','sort_direction' => 'asc'));
		$arrstrSchoolsData = $this->utf8_array_encode( $arrstrSchoolsData );

		$arrstrData 	= array();
		$arrstrListing 	= array();

		if( $arrstrSchoolsData['schools'] ) {
			$i=0;
			foreach ( $arrstrSchoolsData['schools'] as $arrstrSchool ) {
				$arrstrListing[$i] = array( 'ParentID'		=> $arrstrSchool[0]['ParentID'],
											'ParentSchool'	=> $arrstrSchool[0]['ParentSchool'],
											'CampusCount'	=> ( count( $arrstrSchool ) - 1 )
											);
				$i++;
			}
		}
		$arrstrData['schools'] 			= $arrstrListing;
		$arrstrData['total_count']		= $arrstrSchoolsData['school_count'];

		return $arrstrData;

	}

	// subject codes come from taxonomy
	function fetchCollegesBySubjectCodes( $arrstrSubjectCodes ) {
        /* comment this out on dev/prod
        if(mb_detect_encoding($strCourses,'UTF-8')) {
            $arrstrSubjectCodes = utf8_decode($arrstrSubjectCodes);
        }
        */
        include('array_helper.php');
		include_once(COMMON_EDUCATION_PATH.'libraries/ipeds.php');
		$ipeds = new ipeds(array('ipeds_env'=>'dev'));

		$strCourses = implode( ",", $arrstrSubjectCodes );

		foreach( $arrstrSubjectCodes  as $key=>$strSubjectCode ) {
			$arrstrSubjectCodes[$key] = ucwords( strtolower( $strSubjectCode ));
		}
		/*if( false == is_null( $strCourses ) && 0 < strlen( $strCourses )) {
			$arrstrSearch['taxonomy_codes'] = ucwords( strtolower( $strCourses ));
		}*/

		$arrstrSearch['taxonomy_node'] = $arrstrSubjectCodes;

        $arrstrSearch['state'] 		= STATEABBREV;
		$arrstrSearch['country'] 	= 'BR';

        $arrstrSchoolsData = $ipeds->search_schools( $arrstrSearch, array('group_by_field'=>'ParentID', 'sort'=>'ParentSchool','sort_direction' => 'asc'));
		$arrstrSchoolsData = $this->utf8_array_encode( $arrstrSchoolsData );

		$arrstrData 	= array();
		$arrstrListing 	= array();

		if( $arrstrSchoolsData['schools'] ) {
			$i=0;
			foreach ( $arrstrSchoolsData['schools'] as $arrstrSchool ) {
				$arrstrListing[$i] = array( 'ParentID'		=> $arrstrSchool[0]['ParentID'],
											'ParentSchool'	=> $arrstrSchool[0]['ParentSchool'],
											'CampusCount'	=> ( count( $arrstrSchool ) - 1 )
											);
				$i++;
			}
		}
		$arrstrData['schools'] 			= $arrstrListing;
		$arrstrData['total_count']		= $arrstrSchoolsData['school_count'];

		return $arrstrData;

	}

	function fetchCollegesByParentId( $strParentId ) {
		//echo "<h1>function fetchCollegesByParentId</h1>";
		include('array_helper.php');
		include_once(COMMON_EDUCATION_PATH.'libraries/ipeds.php');
		$ipeds = new ipeds(array('ipeds_env'=>'dev'));

		$arrstrSchoolsData = $ipeds->search_schools(array('country'=>'BR','state'=>STATEABBREV, 'parent_id'=>$strParentId ),array('limit'=>'10', 'sort_direction' => 'asc'));
		$arrstrSchoolsData = $this->utf8_array_encode( $arrstrSchoolsData );

        //echo "<pre>";print_r($arrstrSchoolsData);echo "</pre>";

		$arrstrData 	= array();
		$arrstrListing 	= array();

		if( $arrstrSchoolsData['schools'] ) {
			$i=0;
			foreach ( $arrstrSchoolsData['schools'] as $arrstrSchool ) {
                if (!isset($arrstrSchool['PhoneNumber']))
                {
                    $arrstrSchool['PhoneNumber'] = '';
                }
				$arrstrListing[$i] = array(	'InstitutionName' 	=> $arrstrSchool['InstitutionName'] ,
	    									'Address' 			=> $arrstrSchool['Address'] ,
	    									'City'				=> $arrstrSchool['City'] ,
	    									'State' 			=> $arrstrSchool['State'] ,
											'Country' 			=> $arrstrSchool['Country'] ,
											'PhoneNumber' 		=> $arrstrSchool['PhoneNumber'] ,
                                            'Email' 		    => $arrstrSchool['Email'] ,
											'PublicOrPrivate'	=> $arrstrSchool['PublicOrPrivate'] ,
											'InstitutionType'   => $arrstrSchool['InstitutionType'] ,
											'ParentSchool' 		=> $arrstrSchool['ParentSchool'],
											'ParentID'			=> $arrstrSchool['ParentID'],
											//'Ranking'			=> $arrstrSchool['Ranking'],
											'TaxonomyCode'		=> $arrstrSchool['TaxonomyCode'],
											'TaxonomyName'		=> $arrstrSchool['TaxonomyName']
											);

				$arrstrListing[$i]['Website']	= '';
				if( true == isset( $arrstrSchool['Website'] )) {
					$arrstrListing[$i]['Website']	= $arrstrSchool['Website'];
				}

				$i++;
			}
		}

		$arrstrData['schools'] 			= $arrstrListing;
		$arrstrData['total_count']		= $arrstrSchoolsData['school_count'];

		return $arrstrData;
	}

   	/**
     *	Function to get the Page Content using the Uri.
     */
	function getPageContentByURI($uri,$params = NULL) {
		$this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);
		$payload = array(
        	'filterCriteria'=>array(
        						Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME => CE_SITENAME,
                                Platform_Payload_ContentEngine::WEBPAGE_URI_KEY_NAME => htmlentities($uri),
                                self::LOAD_BREADCRUMB_KEY_NAME => Platform_Payload_ContentEngine::VALUE_TRUE,
                                Platform_Payload_ContentEngine::SEARCH_ALTERNATE_URLS_KEY_NAME => Platform_Payload_ContentEngine::VALUE_TRUE,
                                Platform_Payload_ContentEngine::LOAD_GEO_DATA_KEY_NAME => Platform_Payload_ContentEngine::VALUE_TRUE,
                                Platform_Payload_ContentEngine_FilterCriteria::FIELD_SEARCH_ALTERNATE_URIS => Platform_Payload_ContentEngine::VALUE_TRUE,

                               ),
         	'dataValues' => array(
								Platform_Payload_ContentEngine::CLIENT_TYPE_KEY_NAME => self::CLIENT_TYPE,
								Platform_Payload_ContentEngine::IS_ECL_CALL_KEY_NAME => Platform_Payload_ContentEngine::VALUE_TRUE,
								Platform_Payload_ContentEngine::WEBSITE_KEY_NAME => self::ECL_WEBSITE_NAME,
								Platform_Payload_ContentEngine::KEY_NAME_APPLY_FALLBACK => Platform_Payload_ContentEngine::VALUE_TRUE,
								self::FILTER_DEALER_BY_STATE_KEY_NAME => Platform_Payload_ContentEngine::VALUE_TRUE,
                                Platform_Payload_ContentEngine_DataValues::FIELD_ALTERNATE_URI_REDIRECT => Platform_Payload_ContentEngine:: VALUE_TRUE,
							));

		if(isset($params['qualCode'])){
			$payload['dataValues'][Platform_Payload_ContentEngine::QUAL_KEY_NAME] = $params['qualCode'];
		}
		if(isset($params['campusType']) && $params['campusType'] == "CAMPUS") {
			$payload['dataValues'][Platform_Payload_ContentEngine::CAMPUS_TYPE_KEY_NAME] = Platform_Payload_ContentEngine::CAMPUS_TYPE_CAMPUS;
			$payload['dataValues'][Platform_Payload_ContentEngine::MULTI_DEALER_KEY_NAME] = Platform_Payload_ContentEngine::VALUE_YES;
			/* This is because we pass zip only the campusType = CAMPUS. */
			if(isset($params['zip'])){
				$payload['dataValues'][Platform_Payload_ContentEngine::ZIP_KEY_NAME] = $params['zip'];
				$payload['dataValues'][Platform_Payload_ContentEngine::RADIUS_KEY_NAME] = self::RADIUS_VALUE;
			}

			if(isset($params['country'])){
				$payload['dataValues'][Platform_Payload_ContentEngine::COUNTRY_KEY_NAME] = $params['country'];

			}
		} elseif(isset($params['campusType']) && $params['campusType'] == "ONLINE") {
			$payload['dataValues'][Platform_Payload_ContentEngine::CAMPUS_TYPE_KEY_NAME] = Platform_Payload_ContentEngine::CAMPUS_TYPE_ONLINE;
		} else {
			$payload['dataValues'][Platform_Payload_ContentEngine::CAMPUS_TYPE_KEY_NAME] = Platform_Payload_ContentEngine::CAMPUS_TYPE_BOTH;
			$payload['dataValues'][Platform_Payload_ContentEngine::MULTI_DEALER_KEY_NAME] = Platform_Payload_ContentEngine::VALUE_YES;
			if(isset($params['zip'])){
				$payload['dataValues'][Platform_Payload_ContentEngine::ZIP_KEY_NAME] = $params['zip'];
				$payload['dataValues'][Platform_Payload_ContentEngine::RADIUS_KEY_NAME] = self::RADIUS_VALUE;

			}
			if(isset($params['country'])){
				$payload['dataValues'][Platform_Payload_ContentEngine::COUNTRY_KEY_NAME] = $params['country'];
			}
		}
		try	{

            $this->jsonPage = $this->contentEngineService->getPageContent($payload);
            $pageContent = array();
        	$this->page = Platform_ContentEngine_Page::jsonToPage($this->jsonPage);

            $this->page->setAutoRedirect($uri);
        	$this->filterCriteria = $payload['filterCriteria'];
        	$this->dataValue = $payload['dataValues'];
			$sblContent  = $this->getSblContent($this->page);
        }
	 	catch (Exception $e) {
	 		handle_exception($e);
        }

        if($this->page instanceof Platform_ContentEngine_Page && !empty($this->page)){
        	$articleContent['_breadCrumb'] = $this->getbreadCrumbs($this->page->directories);
			$articleContent['_primaryURI'] = $this->page->uri;
			$this->setPrimaryURI($this->page->uri);
			$articleContent['_metaData'] = $this->getMetaData($this->page);
			$articleContent['_blurbData'] = $this->blurbDataWrapper($this->page->elements);
			$articleContent['_listingData'] = $sblContent;

			foreach( $this->page->elements as $objArticle ) {
				if( 'Article' == $objArticle->type) {

					$articleContent['title'] 			= $objArticle->title;
					$articleContent['body'] 			= $objArticle->blurb;
					$articleContent['publishDate'] 		= date('F j, Y', $objArticle->postDate->timestamp );
					$articleContent['postDateTimeStamp']= $objArticle->postDate->timestamp;
					$articleContent['arrstrTags'] 		= $objArticle->tags;
					$articleContent['arrobjTags'] 		= $objArticle->tagsArray;
					$articleContent['images'] 			= $objArticle->images;
				}
			}

        	// special case for GUIA site as we need taxonomy subject in few cases
			require_once('Zend/Json.php');
            $objData = Zend_Json::decode(Hqx_Helper_String::encodeUTF8( $this->jsonPage ), Zend_Json::TYPE_OBJECT);

            if( isset( $objData[1]->content ) && is_array( $objData[1]->content ) )
	        {
	            foreach( $objData[1]->content as $content )
	            {
	                if( isset( $content->blurbsArr ) )
	                {
	                    foreach( $content->blurbsArr as $blurb )
	                    {
	                        if( isset( $blurb->subjectCodes ) && true == is_array( $blurb->subjectCodes ) && 0 < sizeof( $blurb->subjectCodes )) {
	                        	$articleContent['arrstrSubjectCodes'] = $blurb->subjectCodes;
	                        }
	                    }
	                }
	            }
	        }
	        // Cotains much data
	        unset( $objData );
		}
		if(isset($articleContent)) {
			return $articleContent;
		}else{
			show_404();
		}
    }

    /**
     * Function to get the directory listing.
     * @param unknown_type $dir
     */
    function getDirectoryListing($dir) {
    	$this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);
        $payload = array(
        	'filterCriteria' => array(
									Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME => CE_SITENAME,
									Platform_Payload_ContentEngine::DIRECTORY_PATH_KEY_NAME => $dir,
                                    Platform_Payload_ContentEngine::RECURSIVE_SEARCH_ENABLED_KEY_NAME => Platform_Payload_ContentEngine::VALUE_TRUE,
								));

		try
        {
        	$jsonResponse = $this->contentEngineService->getDirectories($payload);
	        $articleListingData = array();
        	$articleListingData = Platform_ContentEngine_Directory::jsonToDirectory($jsonResponse);
        }
		catch (Exception $e) {
            handle_exception($e);
        }
        return $this->dirListingWrapper($articleListingData->children,TRUE);
    }

    /**
     * Function to get the Article Page listing.
     */
    function getArticleListing($dir,$pageNumber = NULL,$perPageLimit = NULL,$maxCount = NULL , $fullData = FALSE) {
    	$this->contentEngineService = new Platform_Service_ContentEngine(CE_ENV);
    	$payload = array(
			'filterCriteria'=>array(
			    	Platform_Payload_ContentEngine::WEBSITE_NAME_KEY_NAME => CE_SITENAME,
			    	Platform_Payload_ContentEngine::DIRECTORY_PATH_KEY_NAME => $dir,
			    	Platform_Payload_ContentEngine::RECURSIVE_SEARCH_ENABLED_KEY_NAME => Platform_Payload_ContentEngine::VALUE_TRUE,
			    	Platform_Payload_ContentEngine::LOAD_ABSTRACT_KEY_NAME => Platform_Payload_ContentEngine::VALUE_TRUE,),
			'sortCriteria' => array(
    				Platform_Payload_ContentEngine::ARTICLE_SORT_BY_POSTDATE_KEY_NAME => Platform_Payload_ContentEngine::SORT_DESC,
    				));
    	if($maxCount != NULL) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = $maxCount;
    	} else {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::MAXCOUNT_KEY_NAME] = 10;
    	}
    	if($pageNumber != NULL && $perPageLimit != NULL) {
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_NUMBER_KEYNAME] = $pageNumber;
    		$payload['filterCriteria'][Platform_Payload_ContentEngine::ARTICLE_PAGE_SIZE_KEYNAME] = $perPageLimit;
    	}
        try {
        	$articleListingData = array();
			$jsonResponse = $this->contentEngineService->getArticlePages($payload);
        	$articleListingData = Platform_ContentEngine_ArticleListing::jsonToArticleListing($jsonResponse);
        }
	 	catch (Exception $e) {
            handle_exception($e);
        }
        if($fullData) {
        	if($articleListingData) {
	        	$articleListData = array();
	        	$articleListData['articleListing'] = $this->articleListingWrapper($articleListingData->articles);
	        	$articleListData['count'] = $articleListingData->totalCount;
	        	return $articleListData;
        	}

        } else {
        	if($articleListingData->articles) {
	        	$listArray = array();
	        	$listArray = $this->dirListingWrapper($articleListingData->articles);
	        	return $listArray;
        	}

        }
    }

    /**
     * Function the Wrap the listing data required for the Article Listing along with some additional Data
     */

    function articleListingWrapper($data) {
    	if(isset($data)) {
	    	$wrappedData = array();
	    	foreach ($data as $value) {
	    		$wrappedData[] = array(	'title' => $value->title ,
	    								'leader' => $value->leader ,
	    								'uri' => $value->uri,
	    								'postDate' => $value->postDate->timestamp);
	    	}
	    	return $wrappedData;
    	} else {
    		return NULL;
    	}
    }
    /**
     * Function to Wrap the listing returned by the ArticleListing or Directory Listing Call.
     * */
    function dirListingWrapper($data,$isdirCall = FALSE) {
    	$modifiedList = array();
    	if($isdirCall) {
			foreach($data as $value) {
	    		$modifiedList[$value->fullNodePath] = $value->displayName;
	    	}

    	}else {
	    	foreach($data as $value) {
	    		if($value->webPageFileName == "index.html")
	    			continue;
	    		$modifiedList[$value->uri] = $value->title;
	    	}
    	}
    	if(isset($modifiedList) && !empty($modifiedList)) {
    		return $modifiedList;
    	}else
    		return NULL;
    }


    /**
     * Function to get the SBL data from the JSON Response.
     */
 	public function getSblContent($response = '') {
        if(empty($response)) {
        	if(empty($this->page)) {
                return null;
            }
            $response = $this->page;
        }

		if(is_object($response)) {
			$businessListingWidget = $response->getPageElementsByType('BusinessListingWidget');
		}
        if(isset($businessListingWidget[0])) {
            $data['schools'] = $businessListingWidget[0]->getVendors();
            $data['widgetInstanceKey'] = $businessListingWidget[0]->getWidgetInstanceKey();
            $data['widgetTracking'] = $businessListingWidget[0]->getTracking()->getTrackingJson();

            $this->filterCriteria[Platform_Payload_ContentEngine::CONTENT_REQUEST_TYPE_KEY_NAME] = self::WIDGET_REQUEST_TYPE;
            $this->filterCriteria[Platform_Payload_ContentEngine::WIDGET_INSTANCE_KEY_NAME] = $data['widgetInstanceKey'];

            $data['filterCriteriaXml'] = Platform_Payload_ContentEngine::arrayToContentEngineXml($this->filterCriteria);
            $data['dataValuesXml'] = Platform_Payload_ContentEngine::arrayToContentEngineXml($this->dataValue);
        }
        else {
            $data['schools'] = array();
            $data['widgetInstanceKey'] = '';
            $data['widgetTracking'] = '';

            $data['filterCriteriaXml'] = '';
            $data['dataValuesXml'] = '';
        }
        return $data;
    }

	/**
	 * Function Name	: blurbDataWrapper()
	 * Description		: Function Wrap the Content One data in the required format
	 */
	private function blurbDataWrapper($data){
		$blurbData = array();
		foreach($data as $key => $obj){

			/* Chceking if its a SBL Widget */
			if($obj instanceof Platform_ContentEngine_Page_SmartBusinessListingWidget && $obj->type == "BusinessListingWidget") {
				continue;
			}
			/* Chceking if its a Article */
			if($obj instanceof Platform_ContentEngine_Page_Article && $obj->type == "Article") {
				$blurbData[$obj->position]['article_title'] 	= $obj->title;
				$blurbData[$obj->position]['postion'] 			= $obj->position;
				$blurbData[$obj->position]['Content'] 			= $obj->blurb;
				$blurbData[$obj->position]['contentType'] 		= $obj->type;

				/* Chceking if its returning Authors */
				if($obj->authors) {
					$blurbData[$obj->position]['author'] 			= !empty($obj->author)?ucwords($obj->author):ucwords("edu411.org admin");
					$blurbData[$obj->position]['authorBio'] 		= $obj->authorBio;
				}

				if($obj->postDate) {
  					$date = date('F j, Y',$obj->postDate->timestamp);
					$blurbData[$obj->position]['postPublishDate'] = $date;
				}

				/* Chceking if any Images are present */
				if($obj->images) {
					foreach($obj->images as $imageObj) {
						if($imageObj instanceof Platform_ContentEngine_Page_Article_Image) {
							$blurbData[$obj->position]['imageName']		= $imageObj->imageName;
							$blurbData[$obj->position]['imagePath'] 	= $imageObj->path;
							$blurbData[$obj->position]['imagealt'] 		= $imageObj->alt;
							$blurbData[$obj->position]['imageWidth']	=!empty($imageObj->width) ? $imageObj->width:142;
							$blurbData[$obj->position]['imageHeight']	=!empty($imageObj->height) ? $imageObj->height:105;
						}
					}
				}
				continue;
			}

			/* Chceking for the Blurb */
			if($obj instanceof Platform_ContentEngine_Page_Blurb && $obj->type == "Blurb") {
				$blurbData[$obj->position]['article_title'] 	= $obj->title;
				$blurbData[$obj->position]['postion'] 			= $obj->position;
				$blurbData[$obj->position]['Content'] 			= $obj->blurb;
				$blurbData[$obj->position]['contentType'] 		= $obj->type;
			}
		}
		return $blurbData;
	}

	/**
	 * Function Name 	: getMetaData()
	 * Description 		: Return the metedata of the particular uri.
	 */
	private function getMetaData($pageContent = NULL){
		$metaData = array();
		if(isset($pageContent)) {
			$metaData['metaTitle'] = $pageContent->metaTitle;
			$metaData['metaKeyword'] = $pageContent->metaKeywords;
			$metaData['metaDesc'] = $pageContent->metaDescription;
			return $metaData;
		}
		return NULL;
	}

	/**
	 * Function Name 	: getbreadCrumbs()
	 * Description 		: Return the breadcrumb of the particular uri.
	 */
	private function getbreadCrumbs($data){

		if(isset($data)) {
			$breadcrumb = array();
			foreach($data as $obj){
				if($obj->URIType == "Primary" && $obj instanceof Platform_ContentEngine_Page_Directory)  {
					if(isset($obj->breadCrumb->details)){
						$breadcrumb = $obj->breadCrumb->details;
						break;
					}
				}
			}
			$breadcrumb[0]->displayName = "Home";
			$breadcrumb[0]->nodePath = "";
			return $breadcrumb;
		}
		return NULL;
	}

	/**
	 * get states by country using the Geo Locate Class used in the state Controller.
	 */
	function getStatesByCountry($country, $flag = 'N', $listStates = false){
		$this->load->cached_library(GEOLOCATE_PATH,array("ce_env"=>CE_ENV));
		$list = $this->geolocate->get_states_by_country($country,$flag);
		if($listStates){
			$stateArray = array();
			foreach($list as $state){
				$stateArray[$state['stateCode']] = $state['stateName'];
			}
			return $stateArray;
		}else{
			return $list;
		}
	}

	/**
	 * Function return the Subject tree.
	 */
	function getSubjectTree($uriToReplace = NULL) {
		$subjectTree = array();
		$uri = "/programs";
		$subjectTree = $this->sblObj->getDirectoryListing($uri);
    	ksort($subjectTree);
		if(isset($uriToReplace) && $uriToReplace != NULL) {
			$modifiedSubjectTree = array();
			foreach ($subjectTree as $key => $value) {
				$modifiedSubjectTree[str_replace(ltrim($uri,"/"), ltrim($uriToReplace,"/"), $key)] = $value;
			}
			return $modifiedSubjectTree;

		}else {
			return $subjectTree;
		}
	}

	/**
	 * Function return the Qualification tree.
	 */
	function getQualTree($uriToReplace = NULL) {
		$subjectTree = array();
		$uri = "/degree";
		$subjectTree = $this->sblObj->getDirectoryListing($uri);
    	ksort($subjectTree);
		if(isset($uriToReplace) && $uriToReplace != NULL) {
			$modifiedSubjectTree = array();
			foreach ($subjectTree as $key => $value) {
				$modifiedSubjectTree[str_replace(ltrim($uri,"/"), ltrim($uriToReplace,"/"), $key)] = $value;
			}
			return $modifiedSubjectTree;

		}else {
			return $subjectTree;
		}

	}

	/**
     * utf-8 decodes arrays
     *
     * @param <array> $input
     * @return <array>
     */
    function utf8_array_decode($input)
    {
        $return = array();
        foreach ($input as $key => $val)
        {
            if( is_array($val) )
            {
                $return[$key] = $this->utf8_array_decode($val);
            }
            else
            {
                $return[$key] = utf8_decode($val);
            }
        }
        return $return;
    }

    /**
     * utf-8 decodes arrays
     *
     * @param <array> $input
     * @return <array>
     */
    function utf8_array_encode($input)
    {
        $return = array();
        foreach ($input as $key => $val)
        {
            if( is_array($val) )
            {
                $return[$key] = $this->utf8_array_encode($val);
            }
            else
            {
                $return[$key] = utf8_encode($val);
            }
        }
        return $return;
    }

}
