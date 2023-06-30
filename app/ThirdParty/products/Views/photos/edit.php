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
								<label for="media">ID файла обложки (<a target="_blank" href="https://help.botcreator.ru/fileid">Как получить?</a>)</label>
								<input required="true" value="<?=$media?>" placeholder="AgACAgIAAxkBAAIE3V6QH9Q0otyZ7IcaYTg2IGL8YiSyAAKArTEbm_54SG8fEzpPAd4FwKjCDwAEAQADAgADeQADkogGAAEYBA" name="media" type="text" id="media" class="form-control">
							</div>

							<div class="form-group">
								<label for="caption">Подпись</label>
								<input value="<?=$caption?>" placeholder="Введите любой текст" name="caption" type="text" id="caption" class="form-control">
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
				<a href="<?=base_url('products/photos/'.$id_item)?>" class="btn btn-secondary">Отмена</a>
				<input type="submit" value="Сохранить" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?=form_hidden('id', $id);?>
	<?=form_close();?>
