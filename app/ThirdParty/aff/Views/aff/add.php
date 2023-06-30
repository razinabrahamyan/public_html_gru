<?=form_open('aff/add');?>
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
					
					<div class="row">
						<div class="col-md-6">
							<div class="form-group">
								<label for="level">Уровень</label>
								<input required="true" name="level" type="number" id="level" class="form-control" value="">
							</div>
						</div>
						<div class="col-md-6">
							<div class="form-group">
								<label for="percent">% от суммы оплаты приведенного клиента</label>
								<input required="true" name="percent" type="text" id="percent" class="form-control" value="">
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
			<a href="<?=base_url('/aff')?>" class="btn btn-secondary">Отмена</a>
			<input type="submit" value="Добавить" class="btn btn-success float-right">
		</div>
	</div>
	<?=form_close();?>