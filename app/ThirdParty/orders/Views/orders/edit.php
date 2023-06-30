<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<?=form_open(uri_string());?>
<div class="row">
	<div class="col-md-12">
		<div class="card card-primary">
			<div class="card-header">
				<h3 class="card-title">Редактировать</h3>
				<div class="card-tools">
					<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
						<i class="fas fa-minus"></i></button>
					</div>
				</div>
				<div class="card-body">
					
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label for="description">Данные заказа</label>
								<textarea name="description" class="form-control" cols=130 rows="5"><?=$data['description']?></textarea>
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label for="status_text">Статус посылки</label>
								<input placeholder="В обработке"  value="<?=$data['status_text']?>" name="status_text" type="text" id="status_text" class="form-control">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label for="delivery_time">Желаемое время доставки</label>
								<input  value="<?=$data['delivery_time']?>" name="delivery_time" type="text" id="delivery_time" class="form-control">
							</div>
						</div>

						<div class="col-md-6">
							<div class="form-group">
								<label for="name_target">Имя получателя</label>
								<input  value="<?=$data['name_target']?>" name="name_target" type="text" id="name_target" class="form-control">
							</div>
						</div>

						<div class="col-md-6">
							
  							<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/css/tempusdominus-bootstrap-4.min.css" />
							<div class="form-group">
								<label for="name_target">Ожидаемая дата получения</label>
				                <div class="input-group date" id="datetimepicker1" data-target-input="nearest">
				                    <input name="date_finish" value="<?=date("Y-m-d H:i", human_to_unix($data['date_finish']))?>" type="text" class="form-control datetimepicker-input" data-target="#datetimepicker1"/>
				                    <div class="input-group-append" data-target="#datetimepicker1" data-toggle="datetimepicker">
				                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
				                    </div>
				                </div>
				            </div>
							
							<script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>
							<script src="<?=base_url('plugins/moment/moment-with-locales.min.js');?>"></script>
							<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/js/tempusdominus-bootstrap-4.min.js"></script>
  							
			                <script>
			                	jQuery(document).ready(function($) {
			                		//https://tempusdominus.github.io/bootstrap-4/Usage/
					                $('#datetimepicker1').datetimepicker({
					                    locale: 'ru',
					                    format: 'YYYY-MM-DD HH:mm' //https://momentjs.com/docs/#/displaying/format/
					                });
					            });
							</script>
						
						</div>

					</div>
				</div>

				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>

	</div>

<div class="row">
		<div class="col-12">
			<p>
				<a href="<?=base_url('orders')?>" class="btn btn-secondary">Отмена</a>
				<input type="submit" value="Сохранить" class="btn btn-success float-right">
			</p>
		</div>
	</div>
<?=form_hidden('id', $id);?>
<?=form_close();?>

<table id="example1" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Сумма (<?=$currency_name?>)</th>
			<th>Продукт</th>
			<th>Единица товара</th>
			<th>Действия</th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ($products as $item):?>
			<tr>
				<td><a href="<?=base_url('orders/edit_item/'.$item['id'])?>"><?=number_format($item['price'], $decimals, ',', ' ');?></a></td>
				<td>
					<a href="<?=base_url('products/edit/'.$item['id_product'])?>">
						<?=json_decode($item['name_product']);?>
					</a>
					<?=$ModModel->mods_item_string($item['id_product_item']);?>
				</td>
				<td><a href="<?=base_url('products/edit_item/'.$item['id_item'])?>"><?=$item['articul']?></a></td>
				<td>
					<a title='Изменить' class='btn btn-success btn-flat' href='<?=base_url("orders/edit_item/" . $item['id'])?>'><i class='fa fa-pencil'></i></a>
					<a title='Удалить' class='btn btn-danger btn-flat' href='<?=base_url("orders/delete_product/" . $item['id'])?>'><i class='fa fa-trash'></i></a>
				</td>
			</tr>
		<?php endforeach;?>
	</tbody>
	<tfoot>
		<tr>
			<th>Сумма (<?=$currency_name?>)</th>
			<th>Продукт</th>
			<th>Единица товара</th>
			<th>Действия</th>
		</tr>
	</tfoot>
</table>