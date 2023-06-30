<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>
<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить фото единицы товара" href="<?=base_url('products/add_photo/'.$id_item)?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example3" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>ID</th>
			<th>Подпись</th>
			<!-- <th>Фото</th> -->
			<th>Операции</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($items as $item):?>
			<tr>
				<td><a href="<?=base_url('products/edit_photo/'.$item['id'])?>"><?=esc($item['id']);?></a></td>
				<td><a href="<?=base_url('products/edit_photo/'.$item['id'])?>"><?=esc($item['caption']);?></a></td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("products/edit_photo/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("products/delete_photo/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
				</td>
				
				
				
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>ID</th>
			<th>Подпись</th>
			<!-- <th>Фото</th> -->
			
			<th>Операции</th>
		</tr>
	</tfoot>
</table>
<blockquote>
	Добавить в альбом можно до 10 фотографий!
	<br>Если добавить меньше 2-х фотографий то кнопка не будет отображаться в боте.
</blockquote>