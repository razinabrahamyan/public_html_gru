<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<table id="example3" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>ID</th>
			<th>Название</th>
			<th>Текст</th>
			<th>Группа</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($pages as $page):?>
			<tr>
				<td><a href="<?=base_url('admin/pages/edit/'.$page['id'])?>"><?=esc($page['id']);?></a></td>
				<td><a href="<?=base_url('admin/pages/edit/'.$page['id'])?>"><?=esc($page['name']);?></a></td>
				<td><a href="<?=base_url('admin/pages/edit/'.$page['id'])?>"><?=$page['text'];?></a></td>
				<td><a href="<?=base_url('admin/pages/edit/'.$page['id'])?>"><?=esc($page['name_group']);?></a></td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>ID</th>
			<th>Название</th>
			<th>Текст</th>
			<th>Группа</th>
		</tr>
	</tfoot>
</table>
<?=$need_languages ? anchor('language/export/pages', "Экспортировать в Excel") : ""?>