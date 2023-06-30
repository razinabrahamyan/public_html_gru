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
					

					<div class="form-group">
	                    <label for="name">Название (Видно пользователям)</label>
	                    <p class="lead emoji-picker-container">
		                    <input required="true" data-emojiable="true" type="text" name="name" class="form-control" id="name" placeholder="Введите название" value="<?=$name;?>">
		                </p>
	                </div>

					<div class="form-group">
						<label for="priority">Приоритет (чем больше, тем выше в списке)</label>
						<input name="priority" type="number" id="priority" class="form-control" value="<?=$priority?>">
					</div>

					<div class="form-group">
						<label for="currency">Валюта</label>
						<input required="true" pattern="^[A-Z]+$" placeholder="RUB" name="currency" type="text" id="currency" class="form-control" value="<?=$currency?>">
					</div>

				</div>

				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>

	</div>

<?php if (count($settings) > 0) {?>
<!-- jQuery -->
<script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>
<!-- jqCron https://github.com/arnapou/jqcron -->
<link rel="stylesheet" href="<?= base_url('/plugins/jqcron/src/jqCron.css'); ?>">
<script src="<?=base_url('/plugins/jqcron/src/jqCron.js');?>"></script>
<script src="<?=base_url('/plugins/jqcron/src/jqCron.ru.js');?>"></script>

<div class="card card-secondary">
	<div class="card-header">
		<h3 class="card-title">Настройки</h3>
		<div class="card-tools">
			<button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
				<i class="fas fa-minus"></i></button>
			</div>
		</div>
		<div class="card-body">
			<div class="row">
				<div class="col-md-12">
					<?php foreach ($settings as $setting){ ?>
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
						<?php } else if ($setting['type'] == "blockquote") { ?>
							<blockquote>
								<?=$setting['comment'];?>
							</blockquote>
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
<?php }?>

	<div class="row">
		<div class="col-12">
			<p>
				<a href="<?=base_url('/pays')?>" class="btn btn-secondary">Отмена</a>
				<input type="submit" value="Сохранить" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?=form_hidden('id', $id);?>
	<?=form_close();?>