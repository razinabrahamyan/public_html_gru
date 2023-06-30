<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>
<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить единицы товара" href="<?=base_url('products/add_item/'.$id_product)?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example3" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Приоритет</th>
			<th>ID</th>
			<th>Дата</th>
			<th>Заказ</th>
			<th>Цена</th>
			<th>Артикул</th>
			<?php if (count($mods) > 0) {?>
			<th>Модификаторы</th>
			<?php }?>
			<th>Операции</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($items as $item):?>
			<tr>
				<td><a href="<?=base_url('products/edit_item/'.$item['id'])?>"><?=esc($item['priority']);?></a></td>
				<td><a href="<?=base_url('products/edit_item/'.$item['id'])?>"><?=esc($item['id']);?></a>  <?=empty($item['file_id']) ? "" : '<i title="Указана картинка" class="far fa-file-image"></i>';?></td>
				<td><a href="<?=base_url('products/edit_item/'.$item['id'])?>"><?=esc($item['created']);?></a></td>
				<td><a href="<?=base_url('products/edit_item/'.$item['id'])?>"><?=esc($item['id_order']);?></a></td>
				<td><a href="<?=base_url('products/edit_item/'.$item['id'])?>"><?=esc($item['price']);?></a></td>
				<td><a href="<?=base_url('products/edit_item/'.$item['id'])?>"><?=esc($item['articul']);?></a></td>
				<?php if (count($mods) > 0) {?>
				<td><a href="<?=base_url('products/edit_item/'.$item['id'])?>"><?=esc($item['mods']);?></a></td>
				<?php }?>
				<td>
					<a title='Копировать единицу товара' class='btn btn-secondary btn-flat' href='<?=base_url("products/copy_item/" . $item['id'])?>'><i class="far fa-copy"></i></a>
					<a title='Фото товара' class='btn btn-primary btn-flat' href='<?=base_url("products/photos/" . $item['id'])?>'><i class="far fa-file-image"></i></a>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("products/edit_item/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("products/delete_item/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
				</td>
				
				
				
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Приоритет</th>
			<th>ID</th>
			<th>Дата</th>
			<th>Заказ</th>
			<th>Цена</th>
			<th>Артикул</th>
			<?php if (count($mods) > 0) {?>
			<th>Модификаторы</th>
			<?php }?>
			<th>Операции</th>
		</tr>
	</tfoot>
</table>