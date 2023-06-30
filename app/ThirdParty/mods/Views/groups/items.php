<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>
<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить группу модификаторов" href="<?=base_url('groups/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example1" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Приоритет</th>
			<th>ID</th>
			<th>Название</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($items as $item):?>
			<tr>
				<td><a href="<?=base_url('groups/edit/'.$item['id'])?>"><?=esc($item['priority']);?></a></td>
				<td><a href="<?=base_url('groups/edit/'.$item['id'])?>"><?=esc($item['id']);?></a></td>
				<td><a href="<?=base_url('groups/edit/'.$item['id'])?>"><?=esc($item['name']);?></a> </td>
				<td>
					<a title='Модификаторы в группе' class='btn btn-default btn-flat' href='<?=base_url("mods/items/" . $item['id'])?>'><i class='fa fa-search'></i></a>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("groups/edit/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("groups/delete/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Приоритет</th>
			<th>ID</th>
			<th>Название</th>
			<th></th>
		</tr>
	</tfoot>
</table>