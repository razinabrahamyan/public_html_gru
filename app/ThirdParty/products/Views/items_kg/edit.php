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
							<div class="col-6">
								<div class="form-group">
									<label for="price">Стоимость в <?=$currency_name?></label>
									<input value="<?=$price;?>" required="true" name="price" type="text" id="price" class="form-control">
								</div>
							</div>
							<div class="col-6">
								<div class="form-group">
									<label for="value">Кол-во единиц товара</label>
									<input value="<?=$value;?>" required="true" name="value" type="number" id="value" class="form-control">
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
					<a href="<?=base_url('products/items_kg/'.$id_product)?>" class="btn btn-secondary">Отмена</a>
					<input type="submit" value="Сохранить" class="btn btn-success float-right">
				</p>
			</div>
		</div>
		<?=form_hidden('id', $id);?>
		<?=form_close();?>
