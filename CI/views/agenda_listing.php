<div id="Trilha">
	<?php $this->load->view('includes/barra_trilha.php'); ?>
</div>
<div id="Listagem">
	<h1><?php echo $strSectionName;?></h1>
    <table id="agendaTable" cellpadding="0" cellspacing="0" background="red">
        <tr>
            <th width="40px">Institui&ccedil;&atilde;o</th>
            <th width="40px">Vestibular</th>
            <th width="20px">Inscri&ccedil;&otilde;es</th>
            <th width="40px">Data Exame</th>
            <th width="40px">Enem</th>
        </tr>
    <?php
	$intCount = 0;
	if( true == is_array( $arrstrArticles ) &&  0 < sizeof( $arrstrArticles )) {
		foreach ( $arrstrArticles as $arrArticle ) { 
            echo $arrArticle['abstract'];
			$intCount++;
		}
	}
    ?>
    </table>
	<?php
	
	if( $intTotalCountArticles > 1 ) { ?>
	<div id="Filtro">
		<p style="FLOAT: right;">
			<?php echo $strPagination;?>
		</p>
		<div id="Clear">&nbsp;</div>
	</div>
	<?php
	}?>
</div>
<div id="Funcoes">
	<?php $this->load->view('includes/barra_funcoes.php'); ?>
</div>