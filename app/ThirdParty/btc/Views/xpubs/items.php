<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>
<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить Xpub" href="<?=base_url('btc/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example3" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>ID</th>
			<th>Дата</th>
			<th>Ключ</th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($items as $item):?>
			<tr>
				<td>
					<a href="<?=base_url('btc/edit/'.$item['id'])?>"><?=$item['id'];?></a>
				</td>
				<td><a href="<?=base_url('btc/edit/'.$item['id'])?>"><?=$item['created'];?></a></td>
				<td>
					<a href="<?=base_url('btc/edit/'.$item['id'])?>"><?=$item['xpub'];?></a>
				</td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("btc/edit/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("btc/delete/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
					
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>ID</th>
			<th>Дата</th>
			<th>Ключ</th>
			<th></th>
		</tr>
	</tfoot>
</table>