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

							<?php if (count($mods) > 0) {?>
							<div class="form-group">
		                        <label>Модификатор(ы)</label>
		                        <select name="id_mods[]" multiple="multiple" class="form-control select2 " data-placeholder="Модификатор" style="width: 100%;">
		                            <?php foreach ($mods as $mod): ?>
		                                <option
		                                <?php 
		                                	foreach ($mods_item as $mod_item) {
		                                		if ($mod_item['id_mod'] == $mod['id']) {
		                                			echo "selected";
		                                		}
		                                	}
		                                ?>
		                                 value="<?= $mod['id'] ?>"><?= $mod['name'] ?> (<?= $mod['name_group'] ?>)</option>
		                            <?php endforeach; ?>
		                        </select>
		                    </div>
		                	<?php } ?>

							<div class="form-group">
								<label for="articul">Артикул</label>
								<input value="<?=$articul?>" placeholder="Введите уникальный артикул единицы товара" required="true" name="articul" type="text" id="articul" class="form-control">
							</div>

							<div class="form-group">
								<label for="file_id">ID файла обложки (<a target="_blank" href="https://help.botcreator.ru/fileid">Как получить?</a>)</label>
								<input value="<?=$file_id?>" placeholder="AgACAgIAAxkBAAIE3V6QH9Q0otyZ7IcaYTg2IGL8YiSyAAKArTEbm_54SG8fEzpPAd4FwKjCDwAEAQADAgADeQADkogGAAEYBA" name="file_id" type="text" id="file_id" class="form-control">
							</div>

							<div class="form-group">
								<label for="price">Стоимость в <?=$currency_name?></label>
								<input title="Изменение стоимости в продукте не влияет на уже созданные заказы!"  value="<?=$price?>" required="true" name="price" type="text" id="price" class="form-control">
							</div>

							<div class="form-group">
								<label for="priority">Приоритет (чем больше, тем выше в списке)</label>
								<input value="<?=$priority?>" name="priority" type="number" id="priority" class="form-control">
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
				<input type="submit" value="Сохранить" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?=form_hidden('id', $id);?>
	<?=form_close();?>
