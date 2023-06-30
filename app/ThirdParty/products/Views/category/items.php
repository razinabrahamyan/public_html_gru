<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>
<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить категорию" href="<?=base_url('category/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    <a class="btn btn-default btn-flat" title="Посмотреть дерево категорий" href="<?=base_url('category/tree')?>"><i class="fas fa-tree"></i> Дерево</a> 
    </p>           
</div>

<table id="example1" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Приоритет</th>
			<th>ID</th>
			<th>Название</th>
			<th>Родитель</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($categoryes as $item):?>
			<tr>
				<td><a href="<?=base_url('category/edit/'.$item['id'])?>"><?=esc($item['priority']);?></a></td>
				<td><a href="<?=base_url('category/edit/'.$item['id'])?>"><?=esc($item['id']);?></a></td>
				<td>
					<a href="<?=base_url('category/edit/'.$item['id'])?>"><?=esc($item['name']);?></a> 
					<?php
						if ($item['bonus'] > 0) {
							echo "<br>Начислять бонус: ".$item['bonus'].' '.$currency_name;
						}
					 ?>
				</td>
				<td><?php 
					$i = 0;
					foreach ($item['parents'] as $parent) {
						if ($i > 0){
							echo ", ";
						}
						echo "<a href='".base_url('category/edit/'.$parent['id'])."'>".$parent['name']."</a>";
						$i++;
					}
				?></td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("category/edit/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("category/delete/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Приоритет</th>
			<th>ID</th>
			<th>Название</th>
			<th>Родитель</th>
			<th></th>
		</tr>
	</tfoot>
</table>