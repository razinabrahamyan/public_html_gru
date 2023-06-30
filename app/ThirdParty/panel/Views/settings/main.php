<!-- jQuery -->
<script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>
<!-- jqCron https://github.com/arnapou/jqcron -->
<link rel="stylesheet" href="<?= base_url('/plugins/jqcron/src/jqCron.css'); ?>">
<script src="<?=base_url('/plugins/jqcron/src/jqCron.js');?>"></script>
<script src="<?=base_url('/plugins/jqcron/src/jqCron.ru.js');?>"></script>

<?=form_open(uri_string());?>
<div class="row">
	<div class="col-md-12">
		<?php if (!empty($message)) {?>
			<div class="alert alert-success alert-dismissible">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
				<h5><i class="icon fas fa-check"></i> ВНИМАНИЕ!</h5>
				<?=$message;?>
			</div>
		<?php } ?>

		<?php foreach($settings as $name => $data) { ?>
		<div class="card card-secondary">
			<div class="card-header">
				<h3 class="card-title"><?=$name?></h3>
				<div class="card-tools">
					<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
						<i class="fas fa-minus"></i></button>
					</div>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<?php foreach ($data as $setting){
								if ($setting['active'] <= 0 OR !empty($setting['category'])){
									continue;
								}
								?>
								<?php if ($setting['type'] == "checkbox") {?>
									<div class="form-group">
										<label>
											<input <?=$setting['value'] > 0 ? "checked" : ""?> type="checkbox" name="<?=$setting['name'];?>" class="minimal">
											<?=$setting['comment'];?> 
										</label>
									</div>
								<?php } else if ($setting['type'] == "cron") {?>
									<p>
	                                    <label><?=$setting['comment'];?><?php

	                                    $locale_time = localtime(time(), true);
	                                    echo " (на сервере ".$locale_time['tm_hour'].":".$locale_time['tm_min'].")";

	                                    ?></label>
	                                    <div id="<?=$setting['name'];?>"></div>
	                                    <input hidden="true" <?=$setting['type'] == "disabled" ? 'disabled' : "";?> id="<?=$setting['name'];?>_"  name="<?=$setting['name'];?>" class="form-control" type="text" value="<?=$setting['value']?>">
	                                </p>
	                                <script>
	                                //выбор частоты повторений задания
									jQuery(document).ready(function( $ ) {
											$('#<?=$setting['name'];?>').jqCron({
													default_value: '* * * * *',
													no_reset_button: false,
													numeric_zero_pad: true,
													enabled_minute: true,
													multiple_dom: true,
													multiple_month: true,
													multiple_mins: true,
													multiple_dow: true,
													multiple_time_hours: true,
													multiple_time_minutes: true,
										        	bind_to: $('#<?=$setting['name'];?>_'), //куда помещать значения
										        	bind_method: {
										        	set: function($element, value) {
										                $element.val(value); //помещать значения
										            }
										        }
										    });
									});
									</script>
								<?php } else {?>
									<p>
										<label><?=$setting['comment'];?></label>
										 <?=$setting['type'] == "encrypted" ? ' <i title="Хранится в зашифрованном виде" class="fas fa-shield-alt"></i>' : "";?>
										 <span title="Тег для использования в шаблонах" class="badge badge-primary">{<?=$setting['name'];?>}</span>
										<input <?=empty($setting['pattern']) ? "" : "pattern='".$setting['pattern']."'"?> <?=empty($setting['type']) ? "" : "type='".$setting['type']."'"?> name="<?=$setting['name'];?>" class="form-control" type="text" value="<?=$setting['value']?>">
									</p>
								<?php }?>
							<?php }?>  

						</div>
					</div>
				</div>
				<!-- /.card-body -->
			</div>
			<!-- /.card -->
			<?php } ?>

		</div>

	</div>
	<div class="row">
		<div class="col-12">
			<p>
				<a href="<?=base_url('/admin')?>" class="btn btn-secondary">Отмена</a>
				<input type="submit" value="Сохранить" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?=form_close();?>
