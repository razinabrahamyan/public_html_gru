<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Добавить уровень" href="<?=base_url('aff/add')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<table id="example1" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Уровень</th>
			<th>Комиссионные %</th>
			<th>Действия</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($items as $item):?>
			<tr>
				<td>
					<a href="<?=base_url('aff/edit/'.$item['id'])?>"><?=esc($item['level']);?></a>
				</td>
				<td><a href="<?=base_url('aff/edit/'.$item['id'])?>"><?=esc($item['percent']);?></a></td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("aff/edit/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("aff/delete/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Уровень</th>
			<th>Комиссионные %</th>
			<th>Действия</th>
		</tr>
	</tfoot>
</table>

<blockquote>
	<u>Уровни партнерской программы</u> - позволяют боту понять - кому из партнеров сколько комиссионных начислить на баланс за оплату приведенного клиента.
	<br>Сумма вознаграждения зависит от того на каком уровне будет находиться приглашенный клиент относительно партнера.
	<br>Подробнее о том что такое партнерская программа можете ознакомиться в <a target="_blank" href="https://youtu.be/dQzEcBpxW_Q">видеоуроке.</a>
</blockquote>