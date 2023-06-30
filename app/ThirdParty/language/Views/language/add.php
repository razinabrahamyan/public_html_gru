<?=form_open('language/add');?>
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
						<label for="name">Название</label>
						<input placeholder="English" required="true" name="name" type="text" id="name" class="form-control" value="">
					</div>
					<div class="form-group">
						<label for="short">Буквенный код языка</label>
						<input placeholder="en" required="true" name="short" type="text" id="short" class="form-control" value="">
					</div>
				</div>

				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>

	</div>
	<div class="row">
		<div class="col-12">
			<a href="<?=base_url('/language')?>" class="btn btn-secondary">Отмена</a>
			<input type="submit" value="Добавить" class="btn btn-success float-right">
		</div>
	</div>
	<?=form_close();?>