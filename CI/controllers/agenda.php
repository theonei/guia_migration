<?php
class Agenda extends HQ_Controller {
	
	protected $strAgendaBaseUrl;
	
	function Agenda() {
		// For PHP4 compatibility
		$this->__construct();
	}
	
	function __construct() {
		parent::__construct();
		
		$this->strAgendaBaseUrl	= BASEURL . "/agenda";
		$this->data['strSectionName'] 		= 'Agenda';
        $this->data['strSectionListingUri']	= 'agenda';
    }
	
	function index() {
        
        $this->load->library('pagination');
		$this->load->helper('listing_helper');
		
		$totalUriSegment = $this->uri->total_segments();
        $uriSegment      = $this->uri->segment( $totalUriSegment );
        $strSegmentArray = $this->uri->segment_array();
       
        $this->data['intCurrentPage']  		= 1;
		if( $uriSegment > 1 ) {
			$this->data['intCurrentPage']  	= $uriSegment;
		}
		
		$arrstrAgendaListing	= $this->sblObj->fetchArticlesByDirectoryNameByPageNumber( '/agenda/' . CE_DIRECTORY_NAME,  $this->data['intCurrentPage'], PAGE_SIZE );
        
        
        $intCount = 0;
		foreach( $arrstrAgendaListing['article_listing'] as $intKey => $arrstrAgenda ) {
            $arrstrAgenda['abstract'] = str_replace('/Agenda', '/agenda', $arrstrAgenda['abstract']);
            $arrstrAgenda['abstract'] = preg_replace('/href=\'http.*br\/agenda/', 'href=\'/agenda', $arrstrAgenda['abstract']);
            $arrstrAgenda['abstract'] = preg_replace('/href="http.*br\/agenda/', 'href="/agenda', $arrstrAgenda['abstract']);
            $abstract = '<tr>'.PHP_EOL;
            $abstractModified	= preg_replace('/<div/', '<td', $arrstrAgenda['abstract']);
            $abstractModified	= preg_replace('/<\/div>/', '</td>', $abstractModified);
            if( $intKey % 2 == 0 ) {
                $abstractModified	= preg_replace("/class='/", "class='cor ", $abstractModified);
            }
            $abstract .= $abstractModified.PHP_EOL;
            $abstract .= '<tr>'.PHP_EOL;
			$arrstrAgendaListing['article_listing'][$intKey]['abstract'] = $abstract;
            $intCount++;
		}
				
		$this->data['arrstrArticles']  			= $arrstrAgendaListing['article_listing'];
		$this->data['intTotalCountArticles']  	= $arrstrAgendaListing['total_count'];
		
		if( $this->data['intTotalCountArticles'] > 0 ) {
        	$arrstrConfig = array();
        	$arrstrConfig = getPaginationConfig( PAGE_SIZE, $this->strAgendaBaseUrl, $this->data['intTotalCountArticles'] );
			$this->pagination->initialize( $arrstrConfig );
			$strPagination = $this->pagination->page_create_links( $this->data['intCurrentPage'] );
        }
        
        $this->data['strPagination'] 	= $strPagination;
        $this->data['metaTitle'] 		= COMMON_META_TITLE . ' : Agenda';
		
		$this->data['strBreadCrumb']				= 'Agenda';
		$this->data['boolShowSendToFriendOption']	= true;
		$this->data['boolShowTier2']  	= true;
		
        // get meta info for landing page (index)
        //$curIndexURI = '/' . $uriSegment . '/' . CE_DIRECTORY_NAME;
        $curIndexURI = '/' . $strSegmentArray[1] . '/' . CE_DIRECTORY_NAME;
        $ind = $this->sblObj->getPageContentByURI($curIndexURI);
        
		$this->set_meta_data( COMMON_META_TITLE . ' : Agenda', $ind['_metaData']['metaKeyword'], $ind['_metaData']['metaDesc'] );
		
		$this->_loadStructure('agenda_listing.php', 'col2-right');
	}
	
	function detail() {
		
		$uri = uri_string();
		$uri = str_replace( '/agenda/', '/agenda/'.CE_DIRECTORY_NAME . '/', $uri );
			
		//Sample API call to get article data. Implement other calls in article_model
		//$article = $this->articles->getArticleData( $uri );
        
        $params					= array();
        //$params['campusType']	= 'CAMPUS';
        //$params['zip'] 			= '40425';
        $params['country'] 		= 'BR';
        $article = $this->sblObj->getPageContentByURI( $uri, $params );
     
        $this->data['content'] 					= $article['body'];
		$this->data['articleTitle'] 			= $article['title'];
		$this->data['articlePublishDate'] 		= $article['publishDate'];
		$this->data['articlepostDateTimeStamp'] = $article['postDateTimeStamp'];
		$this->data['images'] 					= $article['images'];
				
		$this->data['strBreadCrumb']				= '<a href=\''.$this->strAgendaBaseUrl.'\' title=\'Agenda\'>agenda</a>';
		$this->data['boolShowPrintOption']			= true;
		$this->data['boolShowSendToFriendOption']	= true;
		$this->data['boolShowTier2']  	= true;
		
		$strTitle 		= COMMON_META_TITLE .' : Agenda : ' . $article['title'];
		$strKeywords	= '';
		$strDescription	= '';
		if( true == isset( $article['_metaData']['metaTitle'] ) && 0 < strlen( $article['_metaData']['metaTitle'] ) ) {
			$strTitle		= $article['_metaData']['metaTitle'];
		}
		if( true == isset( $article['_metaData']['metaKeyword'] ) && 0 < strlen( $article['_metaData']['metaKeyword'] ) ) {
			$strKeywords	= $article['_metaData']['metaKeyword'];
		}
		if( true == isset( $article['_metaData']['metaDesc'] ) && 0 < strlen( $article['_metaData']['metaDesc'] ) ) {
			$strDescription	= $article['_metaData']['metaDesc'];
		}
		$this->set_meta_data( $strTitle, $strKeywords, $strDescription );
		
		$this->_loadStructure('agenda_detail.php', 'col2-right');
	}
	
	function modifyLeader( $strLeader ) {
		$strModifiedLeader = '';
		if( preg_match( "@<div\sclass='vestibular'>(.*?)</div>@", $strLeader, $matches ) ) {
			
			$strModifiedLeader = $matches[1];
		}
		return $strModifiedLeader;
	}
	
	function error(){
		show_404();
	}
}