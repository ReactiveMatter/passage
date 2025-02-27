<?php
	include("include/head.php");
	include("include/navbar.php");
?>
	<div class="container main">
	<h1 class="title"><?=ucfirst($page['title'])?></h1>
	<?=$page['content']?>
	</div>
</body>
<?php
	include("include/footer.php");
?>