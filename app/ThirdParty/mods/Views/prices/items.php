<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>
<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить продукт" href="<?=base_url('products/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example1" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>ID</th>
			<th>Название</th>
			<th>Цена (<?=$currency_name?>)</th>
			<th>Категория</th>
			<!-- <th>В наличии</th> -->
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($buttons as $item):?>
			<tr>
				<td><a href="<?=base_url('products/edit/'.$item['id'])?>"><?=esc($item['id']);?></a></td>
				<td><a href="<?=base_url('products/edit/'.$item['id'])?>"><?=esc($item['name']);?></a></td>
				<td><a href="<?=base_url('products/edit/'.$item['id'])?>"><?=$item['price'] > 0 ? number_format($item['price'], $decimals, ',', ' ') : "<span title='Выдается пользователю только один раз и бесплатно'>бесплатный демо</span>";?></a></td>
				<td><a href="<?=base_url('products/edit/'.$item['id'])?>"><?php
					$i = 0;
					foreach ($item['categoryes'] as $category) {
						if ($i > 0) {
							echo ", ";
						}
						echo $category['name'];
						$i++;
					}
				?></a></td>
				<!-- <td><a href="<?=base_url('products/items/'.$item['id'])?>"><?=esc($item['count']);?></a></td> -->
				<td>
					<!-- <a title='Единицы товара' class='btn btn-primary btn-flat' href='<?=base_url("products/items/" . $item['id'])?>'><i class="fas fa-file-alt"></i></a> -->
					<!-- <a title='Спасибо за покупку' class='btn btn-default btn-flat' href='<?=base_url("products/thankyou/" . $item['id'])?>'><i class="fas fa-comment-dollar"></i></a> -->
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("products/edit/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("products/delete/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>ID</th>
			<th>Название</th>
			<th>Цена (<?=$currency_name?>)</th>
			<th>Категория</th>
			<!-- <th>В наличии</th> -->
			<th></th>
		</tr>
	</tfoot>
</table>