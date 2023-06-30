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
								<label for="name">Название (видно пользователям при выборе тарифа)</label>
								<p class="lead emoji-picker-container">
									<input value="<?=$name?>" required="true" data-emojiable="true" type="text" name="name" class="form-control" id="name" placeholder="Введите название">
								</p>
							</div>
						</div>

						<div class="col-md-12">
							<div class="form-group">
								<label for="price">Стоимость в <?=$currency_name?></label>
								<input title="Изменение стоимости в продукте не влияет на уже созданные заказы!"  value="<?=$price?>" required="true" name="price" type="text" id="price" class="form-control">
							</div>
						</div>


						<div class="col-md-12">
							<div class="form-group">
								<label for="priority">Приоритет (больше число - выше в списке)</label>
								<input  value="<?=$priority?>" name="priority" type="number" id="priority" class="form-control">
							</div>
						</div>
					</div>

					<div class="row">
						<div class="col-12">
							<div class="form-group">
		                        <label>Категории</label>
		                        <select name="categoryes[]" multiple="multiple"  class="form-control select2 " data-placeholder="Одну или несколько записей" style="width: 100%;">
		                            <?php foreach ($items as $item): ?>
		                                <option value="<?= $item['id'] ?>"
											<?php
		                                    foreach ($categoryes as $category) {
		                                        if ($category['id'] == $item['id']) {
		                                            echo "selected";
		                                        }
		                                    }
		                                    ?>

		                                	><?= $item['name'] ?></option>
		                            <?php endforeach; ?>
		                        </select>
		                    </div>
						</div>
					</div>

					<?php if (count($all_groups) > 0) {?>
					<div class="row">
						<div class="col-12">
							<div class="form-group">
		                        <label>Группы модификаторов</label>
		                        <select name="product_groups[]" multiple="multiple"  class="form-control select2 " data-placeholder="Одну или несколько записей" style="width: 100%;">
		                            <?php foreach ($all_groups as $item): ?>
		                                <option value="<?= $item['id'] ?>"
											<?php
		                                    foreach ($product_groups as $group) {
		                                        if ($group['id_group'] == $item['id']) {
		                                            echo "selected";
		                                        }
		                                    }
		                                    ?>

		                                	><?= $item['name'] ?></option>
		                            <?php endforeach; ?>
		                        </select>
		                    </div>
						</div>
					</div>
					<?php }?>
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
				<input type="submit" value="Сохранить" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?=form_hidden('id', $id);?>
	<?=form_close();?>


	<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Редактировать описание (<a title="Нажми, чтобы посмотреть видеоурок" href="https://help.botcreator.ru/editor2" target="_blank">Инструкция</a>)</h3>
				<div class="card-tools">
					<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
						<i class="fas fa-minus"></i></button>
					</div>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<script> var blocks = <?=$description?>;</script>
								<div url="<?= base_url('products/save_/'.$id) ?>" id="editorjs"></div>
							</div>
						</div>
					</div>
				</div>
				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>
	<div class="col-12">
		<div class="form-group">
			<label for="file_id">ID файла обложки (<a target="_blank" href="https://help.botcreator.ru/fileid">Как получить?</a>)</label>
			<input ajax="true" url="<?=base_url('products/file_id/'.$id)?>" placeholder="AgACAgIAAxkBAAIE3V6QH9Q0otyZ7IcaYTg2IGL8YiSyAAKArTEbm_54SG8fEzpPAd4FwKjCDwAEAQADAgADeQADkogGAAEYBA" name="file_id" type="text" id="file_id" class="form-control" value="<?=$file_id?>">
		</div>
	</div>
</div>

<!-- jQuery -->
<script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>

<!-- https://editorjs.io/ -->
	<style> .ce-block__content {max-width: 100%;} .ce-toolbar__content {max-width: 94%;}</style>
<script src="<?=base_url('/plugins/editor.js-master/dist/link/bundle.js')?>"></script>
<script src="<?=base_url('/plugins/editor.js-master/dist/inline-code/bundle.js')?>"></script><!-- Inline Code -->
<script src="<?=base_url('/plugins/editor.js-master/dist/delimiter/bundle.js')?>"></script> 
	<script src="<?=base_url('/plugins/editor.js-master/dist/strike/bundle.js')?>"></script> 
<script src="<?=base_url('/plugins/editor.js-master/dist/underline/bundle.js')?>"></script> 
<script src="<?=base_url('/plugins/editor.js-master/dist/preformated/bundle.js')?>"></script> 
<script src="<?=base_url('/plugins/editor.js-master/dist/editor.js')?>"></script>
<script src="<?=base_url('/plugins/editor.js-master/dist/ci4editor.js')?>"></script>

<!-- чекбокс на ajax -->
<script src="<?=base_url('/plugins/checkbox/ci4checkbox.js')?>"></script>
<!-- поле ввода на ajax -->
<script src="<?=base_url('/plugins/input/ci4input.js')?>"></script>