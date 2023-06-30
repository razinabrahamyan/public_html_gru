<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Редактировать страницу (<a href="https://help.botcreator.ru/editor2" target="_blank">Инструкция</a>)</h3>
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
								<div url="<?= base_url('admin/pages/save_/'.$id.'/'.$short) ?>" id="editorjs"></div>
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
		<div class="col-1">
			<a href="<?=base_url('/admin/pages')?>" class="btn btn-secondary">Отмена</a>
		</div>

		<div class="col-11">
			<div class="form-group">
                <?php if (count($languages) > 1) {
                foreach ($languages as $lang){?>
                	<a <?=$short == $lang['short'] ? "hidden='true'" : "";?> href="<?=base_url('/admin/pages/edit/'.$id.'/'.$lang['short'])?>" class="btn btn-default"><?=$lang['name']?></a>
	            <?php } } ?>
        	</div>
		</div>
		
		
	</div>
	
	<div class="row">
		<div class="col-12">
			<div class="form-group">
				<label class="checkbox">
					<input <?=$disable_web_page_preview > 0 ? ' checked="checked"' : "";?> url="<?=base_url('/admin/pages/disable_web_page_preview/'.$id)?>" type="checkbox" name="disable_web_page_preview">
					Скрыть превью ссылки
				</label>
			</div>
		</div>
		<div class="col-12">
			<div class="form-group">
				<label for="file_id">ID файла (<a target="_blank" href="https://help.botcreator.ru/fileid">Как получить?</a>)</label>
				<input ajax="true" url="<?=base_url('/admin/pages/file_id/'.$id)?>" placeholder="AgACAgIAAxkBAAIE3V6QH9Q0otyZ7IcaYTg2IGL8YiSyAAKArTEbm_54SG8fEzpPAd4FwKjCDwAEAQADAgADeQADkogGAAEYBA" name="file_id" type="text" id="file_id" class="form-control" value="<?=$file_id?>">
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