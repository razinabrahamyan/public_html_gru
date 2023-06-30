<?php echo form_open('pays/delete/' . $data['id']);?>
<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Удалить?</h3>
				<div class="card-tools">
					<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
						<i class="fas fa-minus"></i></button>
					</div>
				</div>
				<div class="card-body">
					<p>
						<?php echo form_label(lang('Auth.deactivate_confirm_y_label'), 'confirm');?>
						<input type="radio" name="confirm" value="yes" checked="checked">
						<?php echo form_label(lang('Auth.deactivate_confirm_n_label'), 'confirm');?>
						<input type="radio" name="confirm" value="no">
					</p>
				</div>

				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>

	</div>
	<div class="row">
		<div class="col-12">
			<a href="<?=base_url('pays')?>" class="btn btn-secondary">Отмена</a>
			<input type="submit" value="<?=lang('Auth.delete_submit_btn');?>" class="btn btn-success float-right">
		</div>
	</div>
	<?php echo form_hidden('id', $data['id']);?>
	<?php echo form_close();?>