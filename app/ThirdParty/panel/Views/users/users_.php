<div class="pull-right">
	<p>
    <a class="btn btn-danger btn-flat" title="Создать пользователя" href="<?=base_url('admin/users/create')?>"><i class="fa fa-plus-square"></i> Добавить</a> 
    </p>           
</div>

<?php if (!empty($message)) {?>
	<?=$message;?>
<?php } ?>

<script>var ajax_data = '<?=base_url('admin/users/users_')?>';</script>
<table id="ajax_table" class="table table-bordered table-striped dataTable">
    <thead>
    <tr>
    	<th>ID</th>
        <th><?php echo lang('Auth.index_fname_th');?> <?php echo lang('Auth.index_lname_th');?></th>
		<th>Баланс <?=$currency_name?></th>
		<th><?php echo lang('Auth.index_email_th');?></th>
		<th><?php echo lang('Auth.index_groups_th');?></th>
		<th><?php echo lang('Auth.index_status_th');?></th>
		<th><?php echo lang('Auth.index_action_th');?></th>
    </tr>
    </thead>
    <tfoot>
    <tr>
    	<th>ID</th>
        <th><?php echo lang('Auth.index_fname_th');?> <?php echo lang('Auth.index_lname_th');?></th>
		<th>Баланс <?=$currency_name?></th>
		<th><?php echo lang('Auth.index_email_th');?></th>
		<th><?php echo lang('Auth.index_groups_th');?></th>
		<th><?php echo lang('Auth.index_status_th');?></th>
		<th><?php echo lang('Auth.index_action_th');?></th>
    </tr>
    </tfoot>
</table>	

<p>
	<?=anchor('admin/users/export', "Экспортировать в Excel")?>
</p>