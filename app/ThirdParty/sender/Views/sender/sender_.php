<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<div class="pull-right">
    <p>
    <a class="btn btn-danger btn-flat" title="Отправить сообщение" href="<?=base_url('sender/add')?>"><i class="far fa-paper-plane"></i> Отправить</a> 
    </p>           
</div>

<script>var ajax_data = '<?=base_url('sender/index_')?>';</script>
<table id="ajax_table" class="table table-bordered table-striped dataTable">
    <thead>
    <tr>
        <th>№</th>
        <th>Текст</th>
        <th width="100">Добавлено</th>
        <th>Статус</th>
        <th>Сегмент</th>
        <th></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
        <th>№</th>
        <th>Текст</th>
        <th width="100">Добавлено</th>
        <th>Статус</th>
        <th>Сегмент</th>
        <th></th>
    </tr>
    </tfoot>
</table>