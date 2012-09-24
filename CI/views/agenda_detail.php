<div id="Trilha">
	<?php $this->load->view('includes/barra_trilha.php'); ?>
</div>
<div id="Texto">
<?php
foreach( $images as $objImage ) {?>
	<img class="Foto" src="<?php echo UPLOADED_IMAGE_PATH . $objImage->path;?>">
	<?php
}
?>
<?php echo $content; ?>
</div>
<div id="Funcoes">
	<?php $this->load->view('includes/barra_funcoes.php'); ?>
</div>