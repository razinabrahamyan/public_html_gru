<?php echo form_open(uri_string());?>
<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title"><?=lang('Auth.edit_user_heading');?></h3>
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
						<label for="first_name"><?=form_label(lang('Auth.edit_user_fname_label'), 'first_name');?></label>
						<p class="lead emoji-picker-container">
							<input data-emojiable="true" name="first_name" type="text" id="first_name" class="form-control" value="<?=$firstName['value']?>">
						</p>
					</div>
					<div class="form-group">
						<label for="last_name"><?=form_label(lang('Auth.edit_user_lname_label'), 'last_name');?></label>
						<p class="lead emoji-picker-container">
							<input data-emojiable="true" name="last_name" type="text" id="last_name" class="form-control" value="<?=$lastName['value']?>">
						</p>
					</div>
					<div class="form-group">
						<label for="phone"><?=form_label(lang('Auth.edit_user_phone_label'), 'phone');?></label>
						<input name="phone" type="text" id="phone" class="form-control" value="<?=$phone['value']?>">
					</div>
					<div class="form-group">
						<label for="email"><?=form_label(lang('Auth.edit_user_email_label'), 'email');?></label>
						<input name="email" type="email" id="email" class="form-control" value="<?=$email['value']?>">
					</div>
					<div class="form-group">
						<label for="password"><?=form_label(lang('Auth.edit_user_password_label'), 'password');?></label>
						<input name="password" type="password" id="password" class="form-control" value="">
					</div>
					<div class="form-group">
						<label for="password_confirm"><?=form_label(lang('Auth.edit_user_password_confirm_label'), 'password_confirm');?></label>
						<input name="password_confirm" type="password" id="password_confirm" class="form-control" value="">
					</div>
					<div class="form-group">
						<?php if ($ionAuth->isAdmin()): ?>
							<h3><?=lang('Auth.edit_user_groups_heading');?></h3>
							<?php foreach ($groups as $group):?>
								<label class="checkbox">
									<?php
									$gID     = (int)$group['id'];
									$checked = null;
									$item    = null;
									foreach ($currentGroups as $grp)
									{
										if ($gID === (int)$grp->id)
										{
											$checked = ' checked="checked"';
											break;
										}
									}
									?>
									<input type="checkbox" name="groups[]" value="<?php echo $group['id'];?>"<?php echo $checked;?>>
									<?= esc($group['description']) ?>
								</label>
							<?php endforeach?>

						<?php endif ?>
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
				<a href="<?=base_url('/admin/users')?>" class="btn btn-secondary">Отмена</a>
				<input type="submit" value="<?=lang('Auth.edit_user_submit_btn');?>" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?php echo form_hidden('id', $user->id);?>
	<?php echo form_close();?>