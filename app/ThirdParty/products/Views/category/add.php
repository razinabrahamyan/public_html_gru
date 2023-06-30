<?=form_open('category/add');?>
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
						<label for="name">Название (видно пользователям в боте)</label>
						<p class="lead emoji-picker-container">
							<input required="true" data-emojiable="true" type="text" name="name" class="form-control" id="name" placeholder="Введите название" value="">
						</p>
					</div>

					<div class="form-group">
						<label for="priority">Приоритет (больше число - выше в списке)</label>
						<input  value="0" name="priority" type="number" id="priority" class="form-control">
					</div>

                    <div class="form-group">
                        <label>Родительская категория</label>
                        <select name="id_parents[]" multiple="multiple" class="form-control select2 " data-placeholder="Родительская категория" style="width: 100%;">
                            <?php foreach ($items as $item): ?>
                                <option value="<?= $item['id'] ?>"><?= $item['name'] ?> (ID <?= $item['id'] ?>)</option>
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
			<p>
				<a href="<?=base_url('category')?>" class="btn btn-secondary">Отмена</a>
				<input type="submit" value="Добавить" class="btn btn-success float-right">
			</p>
		</div>
	</div>
	<?=form_close();?>