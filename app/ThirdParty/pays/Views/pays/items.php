<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>
<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить способ оплаты" href="<?=base_url('pays/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example3" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Приоритет</th>
			<th>Название</th>
			<th>Валюта</th>
			<th>Статус</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($items as $item):?>
			<tr>
				<td width="4">
					<a href="<?=base_url('pays/edit/'.$item['id'])?>"><?=$item['priority'];?></a>
				</td>
				<td><a href="<?=base_url('pays/edit/'.$item['id'])?>"><?=$item['name'];?></a></td>
				<td>
					<a href="<?=base_url('pays/edit/'.$item['id'])?>"><?=$item['currency'];?></a>
				</td>
				<td>
					<a href="<?=base_url('pays/active/'.$item['id'])?>"><?=$item['active'] ? "Активен" : "Не активен";?></a>
				</td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("pays/edit/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<?php if ($item['is_hand']) {?>
						<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("pays/delete/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
					<?php } ?>
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Приоритет</th>
			<th>Название</th>
			<th>Валюта</th>
			<th>Статус</th>
			<th></th>
		</tr>
	</tfoot>
</table>