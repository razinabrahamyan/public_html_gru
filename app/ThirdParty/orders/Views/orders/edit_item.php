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
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Редактировать</h3>
				<div class="card-tools">
					<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
						<i class="fas fa-minus"></i></button>
					</div>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-6">
							<div class="col-md-12">
							<div class="form-group">
								<label for="price">Цена (<?=$currency_name?>)</label>
								<input value="<?=$price?>" name="price" type="text" id="price" class="form-control">
							</div>
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
		<a href="<?=base_url('/admin/buttons')?>" class="btn btn-secondary">Отмена</a>
		<input type="submit" value="Сохранить" class="btn btn-success float-right">
	</div>
</div>

<?=form_hidden('id', $id); ?>
<?=form_close(); ?>