<?=form_open('pays/add');?>
<div class="row">
	<div class="col-md-12">
		<div class="card card-default">
			<div class="card-header">
				<h3 class="card-title">Добавить</h3>
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
		                    <input required="true" data-emojiable="true" type="text" name="name" class="form-control" id="name" placeholder="Введите название" value="">
		                </p>
	                </div>

					<div class="form-group">
						<label for="priority">Приоритет (чем больше, тем выше в списке)</label>
						<input name="priority" type="number" id="priority" class="form-control" value="">
					</div>

					<div class="form-group">
						<label for="currency">Валюта</label>
						<input required="true" pattern="^[A-Z]+$" placeholder="RUB" name="currency" type="text" id="currency" class="form-control" value="<?=$currency_cod?>">
					</div>
					
					<blockquote>
						Оплата, при выборе такого способа оплаты будет происходить <u>вручную</u>. При оплате от клиента бот запросит фото чека, который будет отправлен в <a target="_blank" href="https://help.botcreator.ru/chat-id">групповой чат админов.</a>
					</blockquote>
				</div>

				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>

	</div>
	<div class="row">
		<div class="col-12">
			<a href="<?=base_url('/language')?>" class="btn btn-secondary">Отмена</a>
			<input type="submit" value="Добавить" class="btn btn-success float-right">
		</div>
	</div>
	<?=form_close();?>