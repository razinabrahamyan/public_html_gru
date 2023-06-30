<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить транзакцию" href="<?=base_url('balance/add/'.$chat_id)?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>
<table id="example1" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Дата</th>
			<th>Сумма</th>
			<th>Комментарий</th>
			<th>Действия</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($items as $item):?>
			<tr>
				<td><a href="<?=base_url('balance/edit/'.$item['id'])?>"><?=esc($item['created']);?></a> <?=$item['finish'] ? '<i title="Транзакция проведена и отображается в балансе" class="fas fa-check-double"></i>' : '<i title="Транзакция не проведена и не влияет на баланс" class="fas fa-ban"></i>'?></td>
				<td><a href="<?=base_url('balance/edit/'.$item['id'])?>"><?=esc($item['value']);?> <?=esc($item['currency']);?></a></td>
				<td><a href="<?=base_url('balance/edit/'.$item['id'])?>"><?=esc($item['comment']);?></a></td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("balance/edit/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("balance/delete/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
					
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Дата</th>
			<th>Сумма</th>
			<th>Комментарий</th>
			<th>Действия</th>
		</tr>
	</tfoot>
</table>