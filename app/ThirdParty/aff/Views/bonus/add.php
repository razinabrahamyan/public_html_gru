<?=form_open('bonus/add');?>
<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Добавить</h3>
				<div class="card-tools">
					<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
						<i class="fas fa-minus"></i></button>
					</div>
				</div>
				<div class="card-body">
					
					<?php if (!empty($message)) {?>
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
							<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
							<?=$message;?>
						</div>
					<?php } ?>

					<div class="form-group">
						<label for="count_pays">Количество продаж</label>
						<input name="count_pays" type="number" id="count_pays" class="form-control" value="">
					</div>
					<div class="form-group">
						<label for="sum">Сумма (<?=$currency_name?>)</label>
						<input required="true" name="sum" type="text" id="sum" class="form-control" value="">
					</div>

					<div class="row">
						<div class="col-12">
							<div class="form-group">
		                        <label>Продукт</label>
		                        <select name="id_product"  class="form-control select2 " data-placeholder="Одну запись" style="width: 100%;">
		                            <?php foreach ($items as $item): ?>
		                                <option value="<?= $item['id'] ?>"><?= $item['name'] ?></option>
		                            <?php endforeach; ?>
		                        </select>
		                    </div>
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
			<a href="<?=base_url('bonus')?>" class="btn btn-secondary">Отмена</a>
			<input type="submit" value="Добавить" class="btn btn-success float-right">
		</div>
	</div>
	<?=form_close();?>