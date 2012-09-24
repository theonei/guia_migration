<?php
require_once("./nomade/include/parametros.php");
require_once($CONFIG["NOMADEDOCROOT"] ."include/portlet.php");
require_once($CONFIG["NOMADEDOCROOT"] ."include/requestVars.php");
require_once($CONFIG["NOMADEDOCROOT"] ."include/util.php");
require_once($CONFIG["NOMADEDOCROOT"] ."framework/php/MySQLLib.php");
require_once($CONFIG["SMARTY_DIR"]    ."Smarty.class.php");
require_once($CONFIG["SITE_PAGEROOT"] . "./phputil/funcoesGUI.php");

// common config file - holds DB and Smarty objecs and common variables
require_once($_SERVER['DOCUMENT_ROOT']. '/config.php');

$smarty->assign("TITULO_PAGINA", $CONFIG["SITE_NOME"] . " : Agenda");
$smarty->assign("DESCRICAO_PAGINA", "");
$smarty->assign("PALAVRAS_CHAVE_PAGINA", "");

$totalRegistros = 0;
$paginaAtual    = $request -> getVarInt("p");
$paginaAtual    = ($paginaAtual == 0) ? 1 : $paginaAtual;

$CodCategoria = $request -> getVarInt("c");
$CodRegiao    = $request -> getVarInt("r");

/*
 * Seleciona os itens da agenda
 */

$strAnd    = ($CodCategoria > 0) ? " AND A.TB_EDITORIA_ID = $CodCategoria " : "";
$strAnd   .= ($CodRegiao > 0) ? " AND A.TB_REGIAO_ID = $CodRegiao " : "";
 
$strSQL    = "SELECT COUNT(A.TB_AGENDA_ID) AS TOTAL
             FROM TB_AGENDA A
       INNER JOIN TB_AGENDA_TB_PRACA APR ON (A.TB_AGENDA_ID = APR.TB_AGENDA_ID)
  LEFT OUTER JOIN TB_INSTITUICAO I ON (A.TB_INSTITUICAO_ID = I.TB_INSTITUICAO_ID) 
	        WHERE A.CTRL_PUBLICADO = 1
	          AND APR.TB_PRACA_ID = $sitePracaID
	          	 $strAnd
			  AND A.DATA_PUBLICACAO <= NOW()
			  AND (A.DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(A.DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')";


$result    = $db -> select($strSQL);
list($k, $v) = each($result);

$regPorPagina   = 15;
$totalRegistros = $v['TOTAL'];

$totalPaginas = $util -> numPaginas($totalRegistros, $regPorPagina);
$registroInicial = $util -> getRegistroInicial($paginaAtual, $totalRegistros, $regPorPagina);
$regOffset = $util -> getMySQLOffset($paginaAtual, $totalRegistros, $regPorPagina);
$registroFinal = $util -> getRegistroFinal($paginaAtual, $totalRegistros, $regPorPagina);
	
for ($i = 1; $i <= $totalPaginas; $i++) {
	$arrPaginas[] = $i;
}

$strSQL    = "SELECT A.TB_AGENDA_ID, A.TITULO, I.NOME AS INSTITUICAO, A.ANO_REFERENCIA, A.SEMESTRE_REFERENCIA, A.OBSERVACOES, A.ARQ_EDITAL
             FROM TB_AGENDA A
       INNER JOIN TB_AGENDA_TB_PRACA APR ON (A.TB_AGENDA_ID = APR.TB_AGENDA_ID)
  LEFT OUTER JOIN TB_INSTITUICAO I ON (A.TB_INSTITUICAO_ID = I.TB_INSTITUICAO_ID) 
	        WHERE A.CTRL_PUBLICADO = 1
	          AND APR.TB_PRACA_ID = $sitePracaID
			  AND A.DATA_PUBLICACAO <= NOW()
                  $strAnd
			  AND (A.DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(A.DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')
	     ORDER BY A.TITULO ASC LIMIT $regOffset, $regPorPagina";
	     
$lstAgenda = $db -> select($strSQL);
$smarty -> assign('lstAgenda', $lstAgenda);

$smarty->assign("TRILHA", "<a href='index.php' title='Voltar para a página principal'>Página inicial</a> : Agenda");

$smarty -> assign('numPaginas', $totalPaginas);
$smarty -> assign('pagina', $paginaAtual);
$smarty -> assign('arrPaginas', $arrPaginas);
$smarty -> assign('regTotal', $totalRegistros);
$smarty -> assign('regInicial', $registroInicial);
$smarty -> assign('regFinal', $registroFinal);
$smarty -> assign('CodCategoria', $CodCategoria);
$smarty -> assign('CodRegiao', $CodRegiao);
$smarty -> assign("paginaTipo", "interna");
$smarty -> display("agenda.tpl");

?>         