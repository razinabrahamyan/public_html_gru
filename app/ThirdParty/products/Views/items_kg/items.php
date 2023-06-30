<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить цену" href="<?=base_url('products/add_item_kg/'.$id_product)?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example3" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Кол-во</th>
			<th>Цена</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($items as $item):?>
			<tr>
				<td><a href="<?=base_url('products/edit_item_kg/'.$item['id'])?>"><?=esc($item['value']);?></a></td>
				<td><a href="<?=base_url('products/edit_item_kg/'.$item['id'])?>"><?=esc($item['price']);?></a></td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("products/edit_item_kg/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("products/delete_item_kg/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Кол-во</th>
			<th>Цена</th>
			<th></th>
		</tr>
	</tfoot>
</table>