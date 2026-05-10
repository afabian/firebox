<?php function todo_list_item($item) { ?>

<tr bgcolor="<?=$item['due']<time() ? '#dddddd' : '#ddffbb'?>">
	<td><?=$item['id']?></td>
	<td><?=$item['title']?></td>
	<td><?=date("Y-m-d h:i:s", $item['due'])?></td>
	<td><?=$item['done'] ? 'Yes' : 'No'?></td>
	<? if (linkto('edit_item')) { ?>
	<td>
		<a href="<?=linkto('edit_item')?>&item_id=<?=$item['id']?>">Edit</a>
		<a href="<?=linkto('done_item')?>&item_id=<?=$item['id']?>">Complete</a>
	</td> <? } ?>
</tr>

<? } ?>

