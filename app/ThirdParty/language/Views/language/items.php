<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить язык" href="<?=base_url('language/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example1" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Название</th>
			<th>Код</th>
			<th>Действия</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($buttons as $item):?>
			<tr>
				<td><a href="<?=base_url('language/edit/'.$item['id'])?>"><?=esc($item['name']);?> <?=$item['is_default'] ? '<i title="Основной" class="far fa-sun"></i>' : "";?></a>

				</td>
				<td><a href="<?=base_url('language/edit/'.$item['id'])?>"><?=esc($item['short']);?></a></td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("language/edit/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<?php if ($item['is_default'] <= 0) {?>
						<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("language/delete/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
					<?php }?>
					<?php if ($item['active']) {?>
						<a title='Отключить' class='btn btn-default btn-flat' href='<?=base_url("language/deactivate/" . $item['id']."/0")?>'><i class="fas fa-toggle-on"></i></a>
					<?php } else {?>
						<a title='Включить' class='btn btn-default btn-flat' href='<?=base_url("language/deactivate/" . $item['id']."/1")?>'><i class="fas fa-toggle-off"></i></a>
					<?php }?>
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Название</th>
			<th>Код</th>
			<th>Действия</th>
		</tr>
	</tfoot>
</table>