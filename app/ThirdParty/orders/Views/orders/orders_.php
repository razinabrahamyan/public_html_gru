<!-- <div class="pull-right">
	<p>
		<a class="btn btn-danger btn-flat" title="Создать заказ" href="<?=base_url('orders/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
	</p>           
</div>
 -->
<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-check"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<script>var ajax_data = '<?=base_url('orders/orders_')?>';</script>
<table id="ajax_table" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Дата</th>
			<th>ID</th>
			<th>Сумма <?=$currency_name?></th>
			<!-- <th>Дата получения</th> -->
			<th>Статус</th>
			<th>Клиент</th>
			<th>Продукты</th>
			<th></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th>Дата</th>
			<th>ID</th>
			<th>Сумма <?=$currency_name?></th>
			<!-- <th>Дата получения</th> -->
			<th>Статус</th>
			<th>Клиент</th>
			<th>Продукты</th>
			<th></th>
		</tr>
	</tfoot>
</table>