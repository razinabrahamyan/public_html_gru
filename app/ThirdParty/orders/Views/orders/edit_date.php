<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<?=form_open(uri_string());?>

<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Редактировать доступ к <strong><?=json_decode($name)?></strong></h3>
				<div class="card-tools">
					<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
						<i class="fas fa-minus"></i></button>
					</div>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-6">
							
  							<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/css/tempusdominus-bootstrap-4.min.css" />
							<div class="form-group">
				                <div class="input-group date" id="datetimepicker1" data-target-input="nearest">
				                    <input name="date_finish" value="<?=date("Y-m-d H:i", human_to_unix($date_finish))?>" type="text" class="form-control datetimepicker-input" data-target="#datetimepicker1"/>
				                    <div class="input-group-append" data-target="#datetimepicker1" data-toggle="datetimepicker">
				                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
				                    </div>
				                </div>
				            </div>
							
							<script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>
							<script src="<?=base_url('plugins/moment/moment-with-locales.min.js');?>"></script>
							<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/js/tempusdominus-bootstrap-4.min.js"></script>
  							
			                <script>
			                	jQuery(document).ready(function($) {
			                		//https://tempusdominus.github.io/bootstrap-4/Usage/
					                $('#datetimepicker1').datetimepicker({
					                    locale: 'ru',
					                    format: 'YYYY-MM-DD HH:mm' //https://momentjs.com/docs/#/displaying/format/
					                });
					            });
							</script>
						
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
		<a href="<?=base_url('/admin/buttons')?>" class="btn btn-secondary">Отмена</a>
		<input type="submit" value="Сохранить" class="btn btn-success float-right">
	</div>
</div>

<?=form_hidden('id', $id); ?>
<?=form_close(); ?>