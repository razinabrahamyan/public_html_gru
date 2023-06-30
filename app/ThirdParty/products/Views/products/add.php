<?=form_open('products/add');?>
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
						<label for="name">Название (видно пользователям при выборе продукта)</label>
						<p class="lead emoji-picker-container">
							<input required="true" data-emojiable="true" type="text" name="name" class="form-control" id="name" placeholder="Введите название" value="">
						</p>
					</div>
					<div class="form-group">
						<label for="price">Стоимость в <?=$currency_name?></label>
						<input required="true" name="price" type="text" id="price" class="form-control" value="">
					</div>

                    <div class="form-group">
                        <label>Категории</label>
                        <select name="categoryes[]" multiple="multiple"  class="form-control select2 " data-placeholder="Одну или несколько записей" style="width: 100%;">
                            <?php foreach ($items as $item): ?>
                                <option value="<?= $item['id'] ?>"><?= $item['name'] ?> (ID <?= $item['id'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
						<label for="file_id">ID файла обложки (<a target="_blank" href="https://help.botcreator.ru/fileid">Как получить?</a>)</label>
						<input placeholder="AgACAgIAAxkBAAIE3V6QH9Q0otyZ7IcaYTg2IGL8YiSyAAKArTEbm_54SG8fEzpPAd4FwKjCDwAEAQADAgADeQADkogGAAEYBA" name="file_id" type="text" id="file_id" class="form-control" value="">
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
				<a href="<?=base_url('/products')?>" class="btn btn-secondary">Отмена</a>
				<input type="submit" value="Добавить" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?=form_close();?>