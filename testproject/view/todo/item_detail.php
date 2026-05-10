<?php function item_detail($item) { ?>

<h1>Firebox To-Do: <?=isset($item) ? $item['title'] : 'New item'?></h1>

<?php if (isset($item)) { ?>
<table border="1">
	<tbody>
		<?=display('todo_list_item', $item)?>
	</tbody>
</table>
<?php } ?>

<br>

<form action="<?=linkto('save')?>" method="POST">

<input type="hidden" name="item_id" value="<?=isset($item) ? $item['id'] : ''?>"/>

<table border="0">
	<tr>
		<td>Title</td>
		<td><input type="text" name="title" value="<?=isset($item) ? $item['title'] : ''?>"/></td>
	</tr>
	<tr>
		<td>Due Date</td>
		<td><input type="text" name="due" value="<?=date("Y-m-d H:i:s", $item ? $item['due'] : time())?>" /></td>
	</tr>
	<tr>
		<td>Details</td>
		<td><textarea name="details" rows="5" cols="40"><?=isset($item) ? $item['details'] : ''?></textarea></td>
	</tr>
</table>

<br>

<input type="submit" value="Save Item">

</form>

<?php } ?>
