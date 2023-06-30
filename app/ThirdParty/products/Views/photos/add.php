<?=form_open_multipart('products/add_photo/'.$id_item);?>
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

					<div class="col-md-12">
						<div class="form-group">
							<label for="media">ID файла картинки (<a target="_blank" href="https://help.botcreator.ru/fileid">Как получить?</a>)</label>
							<input required="true" placeholder="AgACAgIAAxkBAAIE3V6QH9Q0otyZ7IcaYTg2IGL8YiSyAAKArTEbm_54SG8fEzpPAd4FwKjCDwAEAQADAgADeQADkogGAAEYBA" name="media" type="text" id="media" class="form-control">
						</div>

						<div class="form-group">
							<label for="caption">Подпись</label>
							<input placeholder="Любой текст" name="caption" type="text" id="caption" class="form-control">
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
				<a href="<?=base_url('products/photos/'.$id_product)?>" class="btn btn-secondary">Отмена</a>
				<input type="submit" value="Добавить" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?=form_hidden('id_item', $id_item);?>
	<?=form_close();?>