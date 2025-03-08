<?php
	include("include/head.php");
	include("include/navbar.php");
	
 	// Use usort to sort the $pages array
	usort($pages, function($a, $b){
		if(!isset($a['date']))
		{
			$a['date'] = -1;
		}

		if(!isset($b['date']))
		{
			$b['date'] = -1;
		}

    	return $b['date'] - $a['date'];
	});



?>
<div class="container main page-list">
<h1 class="title"><?=ucfirst($page['title'])?></h1>

<table class="table page-results">
	<thead>
		<tr>
			<th class="head-title" scope="col">Title</th>
			<th class=" head-category" scope="col">Category</th>
			<th class="head-tag" scope="col">Tag</th>
			<th class="head-date text-right" scope="col">Date</th>
		</tr>
	</thead>
	<tbody>
<?php 	$count = 0; 
$filter = $page['filter'];
?>
<?php foreach ($pages as $page): ?>
<?php if (str_starts_with($page['slug'], 'blog/') && $page['category']== $filter): ?>
		<?php
		if($page['slug']=="blog/") { continue;}
		$count++; ?>

		<tr>
		<td class="td-title">
		<a class="title" href="<?php echo $site['base']."/".$page['slug']; ?>">
		<?php echo ucfirst($page['title']); ?>
		</a>
		</td>
	<!-- Category -->
		<td class="td-category">
		<a class="category" href="<?=$site['base']."/category/".strtolower($page['category'])?>"><?=ucfirst($page['category'])?></a>
		</td>
	<!-- Tags -->
		<td class="td-tags">
		<?php foreach ($page['tags'] as $tag => $value): ?>
		<a class="tag" href="<?=$site['base']."/tag/".strtolower($value)?>"><?=ucfirst($value)?></a>
		
		<?php endforeach ?>
		</td>

	<!-- Creation date -->
		<td class="text-right td-date">
		<?php 
			if(isset($page['date']))
			{ echo date($site['date-format'], $page['date']);
			}
		 ?>
		</td>
	</tr>
<?php endif ?>
<?php endforeach ?>
<tr><td colspan="4" class="text-muted">Total posts: <?=$count?></td></tr>
</tbody>
</table>

</div>
<?php
	include("include/footer.php");
?>