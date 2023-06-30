<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<table id="example1" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>ID</th>
			<th>Название</th>
			<th>Команда/URL</th>
			<th>Меню</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($buttons as $item):?>
			<tr>
				<td><a href="<?=base_url('admin/buttons/edit/'.$item['id'])?>"><?=esc($item['id']);?></a></td>
				<td><a href="<?=base_url('admin/buttons/edit/'.$item['id'])?>"><?=$item['name'];?></a></td>
				<td><a href="<?=base_url('admin/buttons/edit/'.$item['id'])?>"><?=empty($item['comand']) ? esc($item['url']) : esc($item['comand']);?></a></td>
				<td><a href="<?=base_url('admin/buttons/edit/'.$item['id'])?>"><?=esc($item['name_menu']);?></a></td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>ID</th>
			<th>Название</th>
			<th>Команда/URL</th>
			<th>Меню</th>
		</tr>
	</tfoot>
</table>

<?=$need_languages ? anchor('language/export/menu_buttons', "Экспортировать в Excel") : ""?>