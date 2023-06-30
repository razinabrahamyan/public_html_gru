<?=form_open('orders/add');?>
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
                        <label>Пользователь</label>
                        <select name="chat_id" class="form-control select2 " data-placeholder="Одну запись" style="width: 100%;">
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= json_decode($user['first_name']) ?> <?= json_decode($user['last_name']) ?> (<?= $user['id'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Способ оплаты</label>
                        <select name="id_pay" class="form-control select2 " data-placeholder="Одну запись" style="width: 100%;">
                            <?php foreach ($pays as $pay): ?>
                                <option value="<?= $pay['id'] ?>"><?= $pay['name']; ?> (<?= $pay['currency'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Продукт (ы)</label>
                        <select required="true" name="products[]" multiple="multiple"  class="form-control select2 " data-placeholder="Одну или несколько записей" style="width: 100%;">
                            <?php foreach ($products as $item): ?>
                                <option value="<?= $item['id'] ?>"><?= $item['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

				</div>

				<!-- /.card-body -->
			</div>
			<!-- /.card -->
		</div>

	</div>
	<div class="row">
		<div class="col-12">
			<a href="<?=base_url('/orders')?>" class="btn btn-secondary">Отмена</a>
			<input type="submit" value="Добавить" class="btn btn-success float-right">
		</div>
	</div>
	<?=form_close();?>