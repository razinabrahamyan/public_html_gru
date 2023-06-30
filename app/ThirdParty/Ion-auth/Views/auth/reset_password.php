<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title><?=lang('Auth.reset_password_heading');?></title>
	<!-- Tell the browser to be responsive to screen width -->
	<meta name="viewport" content="width=device-width, initial-scale=1">

	<!-- Font Awesome -->
	<script src="https://kit.fontawesome.com/e2eeb5e2ac.js"></script>
	<!-- Ionicons -->
	<link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
	<!-- icheck bootstrap -->
	<link rel="stylesheet" href="<?=base_url('/plugins/icheck-bootstrap/icheck-bootstrap.min.css');?>">
	<!-- Theme style -->
	<link rel="stylesheet" href="<?=base_url('/assets/css/adminlte.min.css');?>">
	<!-- Google Font: Source Sans Pro -->
	<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
<body class="hold-transition login-page">
	<div class="login-box">
		<div class="login-logo">
			<a href="<?=base_url('auth/reset_password/' . $code)?>"><?=lang('Auth.reset_password_heading');?></a>
		</div>
		<!-- /.login-logo -->
		<div class="card">
			<div class="card-body login-card-body">
				<p class="login-box-msg"><?=lang('Auth.reset_password_subheading');?></p>
				<?php if (!empty($message)) {?>
					<div class="alert alert-danger alert-dismissible">
						<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
						<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
						<?=$message;?>
					</div>
				<?php } ?>

				<?=form_open('auth/reset_password/' . $code);?>
				<div class="input-group mb-3">
					<input name="new" type="password" class="form-control" placeholder="<?php echo sprintf(lang('Auth.reset_password_new_password_label'), $minPasswordLength);?>">
					<div class="input-group-append">
						<div class="input-group-text">
							<span class="fas fa-lock"></span>
						</div>
					</div>
				</div>
				<div class="input-group mb-3">
					<input name="new_confirm" type="password" class="form-control" placeholder="<?=lang('Auth.reset_password_new_password_confirm_label');?>">
					<div class="input-group-append">
						<div class="input-group-text">
							<span class="fas fa-lock"></span>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-12">
						<button type="submit" class="btn btn-primary btn-block"><?=lang('Auth.reset_password_submit_btn')?></button>
					</div>
					<!-- /.col -->
				</div>

				<?=form_input($user_id);?>
				<?=form_close();?>

				<p class="mt-3 mb-1">
					<a href="<?=base_url('/auth/login')?>"><?=lang('Auth.login_submit_btn');?></a>
				</p>
			</div>
			<!-- /.login-card-body -->
		</div>
	</div>
	<!-- /.login-box -->

	<!-- jQuery -->
	<script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>
	<!-- Bootstrap 4 -->
	<script src="<?=base_url('/plugins/bootstrap/js/bootstrap.bundle.min.js');?>"></script>
	<!-- AdminLTE App -->
	<script src="<?=base_url('/assets/js/adminlte.min.js');?>"></script>

</body>
</html>
