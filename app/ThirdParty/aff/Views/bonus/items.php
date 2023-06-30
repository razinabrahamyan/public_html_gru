<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить настройку бонуса" href="<?=base_url('bonus/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example1" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Продукт</th>
			<th>Количество оплат</th>
			<th>Сумма (<?=$currency_name?>)</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($items as $item):?>
			<tr>
				<td>
					<a href="<?=base_url('bonus/edit/'.$item['id'])?>"><?=esc($item['name']);?></a>
				</td>
				<td>
					<a href="<?=base_url('bonus/edit/'.$item['id'])?>"><?=esc($item['count_pays']);?></a>
				</td>
				<td><a href="<?=base_url('bonus/edit/'.$item['id'])?>"><?=esc($item['sum']);?></a></td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("bonus/edit/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("bonus/delete/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Продукт</th>
			<th>Количество оплат</th>
			<th>Сумма (<?=$currency_name?>)</th>
			<th></th>
		</tr>
	</tfoot>
</table>
<blockquote>
	Единоразовое вознаграждение партнера, когда приведенный клиент совершает заданное количество оплат.
</blockquote>