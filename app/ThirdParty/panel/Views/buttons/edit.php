<?php echo form_open(uri_string());?>
<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Редактировать кнопку <?=$data['name']?></h3>
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
			                <?php  foreach ($languages as $lang){?>
			                	<div class="form-group">
				                    <label for="name<?=$lang['short'];?>">Название (<?=$lang['name']?>)</label>
				                    <p class="lead emoji-picker-container">
					                    <input data-emojiable="true" type="text" name="name<?=$lang['short'];?>" class="form-control" id="name<?=$lang['short'];?>" placeholder="Введите название" value="<?=$translate[$lang['short']];?>">
					                </p>
				                </div>
			                <?php } ?>
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
<?php echo form_hidden('id', $data['id']);?>
<?php echo form_close();?>