<?php echo form_open('admin/users/delete/' . $user->id);?>
<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title"><?=lang('Auth.delete_heading');?> <?=json_decode($user->first_name)?> <?=json_decode($user->last_name)?></h3>
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
			<a href="<?=base_url('/admin/users')?>" class="btn btn-secondary">Отмена</a>
			<input type="submit" value="<?=lang('Auth.delete_submit_btn');?>" class="btn btn-success float-right">
		</div>
	</div>
	<?php echo form_hidden('id', $user->id);?>
	<?php echo form_close();?>