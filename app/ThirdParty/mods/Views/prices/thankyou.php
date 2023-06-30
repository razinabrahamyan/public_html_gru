<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Редактировать текст страницы "спасибо за покупку" (<a title="Нажми, чтобы посмотреть видеоурок" href="https://help.botcreator.ru/editor2" target="_blank">Инструкция</a>)</h3>
				<div class="card-tools">
					<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
						<i class="fas fa-minus"></i></button>
					</div>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<script> var blocks = <?=$thankyou?>;</script>
								<div url="<?= base_url('products/thankyou_/'.$id) ?>" id="editorjs"></div>
							</div>
						</div>
					</div>
				</div>
				<!-- /.card-body -->
		</div>
		<!-- /.card -->
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


<div class="row">
	<div class="col-12">
		<p>
			<a href="<?=base_url('/products')?>" class="btn btn-secondary">Отмена</a>
		</p>
	</div>
</div>