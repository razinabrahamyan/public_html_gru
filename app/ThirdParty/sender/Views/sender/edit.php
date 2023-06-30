<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Редактировать сообщение (<a href="https://help.botcreator.ru/editor2" target="_blank">Инструкция</a>)</h3>
				<div class="card-tools">
					<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
						<i class="fas fa-minus"></i></button>
					</div>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<script> var blocks = <?=$text?>;</script>
								<div url="<?= base_url('sender/save_/'.$id) ?>" id="editorjs"></div>
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
			<div class="form-group">
		        <label>Сегмент(ы)</label>
		        <select required="true"  url="<?=base_url('sender/products_/'.$id)?>" name="products[]" multiple="multiple"  class="form-control select2 " data-placeholder="Одну или несколько записей" style="width: 100%;">
		            <?php foreach ($products as $item): ?>
		                <option
						<?php
							foreach ($segment_posting as $id_product) {
								if ($item['id'] == $id_product) {
									echo "selected";
								}
							}
						?>
		                 value="<?= $item['id'] ?>"><?= $item['name'] ?></option>
		            <?php endforeach; ?>
		        </select>
		    </div>
		</div>
		<div class="col-12">
			<div class="form-group">
				<label class="checkbox">
					<input <?=$disable_web_page_preview > 0 ? ' checked="checked"' : "";?> url="<?=base_url('sender/disable_web_page_preview/'.$id)?>" type="checkbox" name="disable_web_page_preview">
					Скрыть превью ссылки
				</label>
			</div>
		</div>
		
		<div class="col-12">
			<div class="form-group">
				<label for="file_id">ID файла (<a target="_blank" href="https://help.botcreator.ru/fileid">Как получить?</a>)</label>
				<input ajax="true" url="<?=base_url('sender/file_id/'.$id)?>" placeholder="AgACAgIAAxkBAAIE3V6QH9Q0otyZ7IcaYTg2IGL8YiSyAAKArTEbm_54SG8fEzpPAd4FwKjCDwAEAQADAgADeQADkogGAAEYBA" name="file_id" type="text" id="file_id" class="form-control" value="<?=$file_id?>">
			</div>
		</div>

		<!-- <div class="col-12">
			<div class="form-group">
				<label for="sum_bonus">Сумма бонуса в <?=$currency_name?> за нажатие кнопки в посте (0 - не показывать кнопку)</label>
				<input ajax="true" url="<?=base_url('sender/sum_bonus/'.$id)?>" placeholder="100" name="sum_bonus" type="text" id="sum_bonus" class="form-control" value="<?=$sum_bonus?>">
				<blockquote>При нажатии на такую кнопку, 
				пользователь получит указанную сумму на внутренний баланс бота, который сможет вывести по запросу как обычные комиссионные.</blockquote>
			</div>
		</div> -->
	</div>

	<div class="row">
		<div class="col-12">
			<p>
				<a href="<?=base_url('sender')?>" class="btn btn-secondary">Отмена</a>
				<?php if (!$finished) {?>
					<a href="<?=base_url('sender/finish/'.$id)?>" title="Отправить сообщение в очередь рассылки" class="btn btn-danger float-right">Отправить</a>
				<?php } ?>
			</p>
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

	<script src="<?=base_url('/plugins/select2/js/select2.full.min.js');?>"></script>
	<script src="<?=base_url('/plugins/select2/js/ci4select2.js');?>"></script>