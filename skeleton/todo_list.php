<? function todo_list($todo) { ?>

<h1>Firebox To-Do List</h1>

<table border="1">
	<thead>
		<th>ID</th>
		<th>Title</th>
		<th>Due</th>
		<th>Done?</th>
		<th>Actions</th>
	</thead>
	<tbody>
		<? if (count($todo)) foreach ($todo as $item) { ?>
		<?=display('todo_list_item', $item)?>
		<? } ?>
	</tbody>
</table>

<br>

<a href="<?=linkto('create_form')?>">Create New Item</a>

<? } ?>