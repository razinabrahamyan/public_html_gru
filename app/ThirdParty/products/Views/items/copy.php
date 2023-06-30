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
					
					<?php if (!empty($message)) {?>
						<div class="alert alert-danger alert-dismissible">
							<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
							<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
							<?=$message;?>
						</div>
					<?php } ?>
					
					<div class="row">

						<div class="col-md-12">
							<div class="form-group">
		                        <label>Укажите продукт(ы) в которые перенести копию единицы товара <?=$articul?></label>
		                        <select name="id_products[]" multiple="multiple" class="form-control select2 " data-placeholder="продукты" style="width: 100%;">
		                            <?php foreach ($items as $item): ?>
		                                <option value="<?= $item['id'] ?>"><?= $item['name'] ?> (ID <?= $item['id'] ?>)</option>
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
			<p>
				<a href="<?=base_url('products/items/'.$id_product)?>" class="btn btn-secondary">Отмена</a>
				<input type="submit" value="Копировать" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?=form_hidden('id', $id);?>
	<?=form_close();?>
