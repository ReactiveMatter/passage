<?php
	include("include/head.php");
	include("include/navbar.php");
?>
	<div class="container main page blog">
	<h1 class="title"><?=ucfirst($page['title'])?></h1>
	<div class="details text-muted"><span class="date">
		<?php 
		if(isset($page['date']))
			{ echo date($site['date-format'], $page['date']);
			}
		?>
		</span>
	<span class="ml-auto category"><a class="category" href="<?=$site['base']."/category/".strtolower($page['category'])?>"><?=ucfirst($page['category'])?></a></span>
	</div>
	<?=$page['content']?>
	</div>
	<?php if (isset($page['tags']) && sizeof($page['tags']) > 1){
		echo '<p class="tags">Tags: '.implode(', ', $page['tags'])."</p>";
	}
	?>

<?php
	include("include/footer.php");
?>