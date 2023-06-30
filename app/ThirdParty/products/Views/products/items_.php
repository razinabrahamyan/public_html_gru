<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить продукт" href="<?=base_url('products/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-check"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<script>var ajax_data = '<?=base_url('products/products_')?>';</script>
<table id="ajax_table" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th width="50">Приоритет</th>
			<th>ID</th>
			<th>Название</th>
			<th>Цена (<?=$currency_name?>)</th>
			<th>Категория</th>
			<th>В наличии</th>
			<th></th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th width="50">Приоритет</th>
			<th>ID</th>
			<th>Название</th>
			<th>Цена (<?=$currency_name?>)</th>
			<th>Категория</th>
			<th>В наличии</th>
			<th></th>
		</tr>
	</tfoot>
</table>