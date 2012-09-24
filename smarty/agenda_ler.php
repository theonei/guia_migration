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

$smarty->assign("DESCRICAO_PAGINA", "");
$smarty->assign("PALAVRAS_CHAVE_PAGINA", "");

$CodAgenda = $request -> getVarInt("id");
$CodCategoria = $request -> getVarInt("c");

$strAnd = ($CodCategoria > 0) ? " AND A.TB_EDITORIA_ID = $CodCategoria " : "";

/*
 * Seleciona dados do evento
 */
 
$strSQL    = "SELECT A.TB_AGENDA_ID, A.TITULO, A.ANO_REFERENCIA, A.SEMESTRE_REFERENCIA,
                     A.CTRL_PROUNI, A.CTRL_ENEM, A.CTRL_FIES, A.LIVROS, A.TAXA_INSCRICAO,
					 A.DIA_INSCRICAO, A.DIA_PROVAS, A.DIA_RESULTADOS, A.LOCAL_PROVAS,
					 A.OBSERVACOES, A.DATA_PUBLICACAO,
					 F.TB_FOTO_ID, F.FOTO_PEQUENA, F.FOTO_GRANDE, A.ARQ_EDITAL
             FROM TB_AGENDA A
       INNER JOIN TB_AGENDA_TB_PRACA APR ON (A.TB_AGENDA_ID = APR.TB_AGENDA_ID)
  LEFT OUTER JOIN TB_FOTO F ON (A.TB_FOTO_ID = F.TB_FOTO_ID)
	        WHERE A.CTRL_PUBLICADO = 1
	          AND APR.TB_PRACA_ID = $sitePracaID
			  AND A.DATA_PUBLICACAO <= NOW()
			  AND A.TB_AGENDA_ID = $CodAgenda
			  AND (A.DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(A.DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')";
	     
$result = $db -> select($strSQL);
$smarty -> assign('lstAgenda', $result);

list($k, $linha) = each($result);

$smarty->assign("TITULO_PAGINA", $CONFIG["SITE_NOME"] . " : Agenda : " . $linha['TITULO']);
$smarty->assign("TRILHA", "<a href='index.php' title='Voltar para a página principal'>Página inicial</a> : <a href='agenda.php' title='Agenda'>Agenda</a>");

$strSQL    = "SELECT A.TB_AGENDA_ID, A.TITULO,  A.ANO_REFERENCIA, A.SEMESTRE_REFERENCIA, A.OBSERVACOES, A.ARQ_EDITAL
             FROM TB_AGENDA A
       INNER JOIN TB_AGENDA_TB_PRACA APR ON (A.TB_AGENDA_ID = APR.TB_AGENDA_ID)
	        WHERE A.CTRL_PUBLICADO = 1
	          AND APR.TB_PRACA_ID = $sitePracaID
			  AND A.DATA_PUBLICACAO <= NOW()
			  AND (A.INDICE_DESTAQUE = 1 OR A.INDICE_DESTAQUE = 2)
			  AND A.TB_AGENDA_ID <> $CodAgenda
                  $strAnd
			  AND (A.DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(A.DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')
	     ORDER BY A.DATA_PUBLICACAO ASC ";

$result = $db -> select($strSQL);
$smarty -> assign('lstEventosDestaque', $result);

$strSQL    = "SELECT A.TB_AGENDA_ID, A.TITULO,  A.ANO_REFERENCIA, A.SEMESTRE_REFERENCIA, A.OBSERVACOES, A.ARQ_EDITAL
             FROM TB_AGENDA A
       INNER JOIN TB_AGENDA_TB_PRACA APR ON (A.TB_AGENDA_ID = APR.TB_AGENDA_ID)
	        WHERE A.CTRL_PUBLICADO = 1
	          AND APR.TB_PRACA_ID = $sitePracaID
			  AND A.DATA_PUBLICACAO <= NOW()
			  AND (A.INDICE_DESTAQUE IS NULL OR A.INDICE_DESTAQUE BETWEEN 3 AND 4)
			  AND A.TB_AGENDA_ID <> $CodAgenda
                  $strAnd
			  AND (A.DATA_DESPUBLICACAO > NOW() OR DATE_FORMAT(A.DATA_DESPUBLICACAO, '%d/%m/%Y') = '00/00/0000')
	     ORDER BY A.DATA_PUBLICACAO ASC ";

$result = $db -> select($strSQL);
$smarty -> assign('lstOutrosEventos', $result);
	     
$smarty -> assign("paginaTipo", "interna");
$smarty -> display("agenda_ler.tpl");

?>         