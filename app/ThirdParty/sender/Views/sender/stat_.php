<?php if (!empty($message)) {?>
	<div class="alert alert-danger alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-ban"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>



<div class="chart tab-pane" id="sales-chart" style="position: relative; height: 300px;"></div>

<p>
<script>var ajax_data = '<?=base_url('sender/stat_/'.$id)?>';</script>
<table id="ajax_table" class="table table-bordered table-striped dataTable">
    <thead>
        <tr>
            <th>Дата</th>
            <th>Пользователь</th>
            <th>Статус</th>
        </tr>
    </thead>
    <tfoot>
        <tr>
            <th>Дата</th>
            <th>Пользователь</th>
            <th>Статус</th>
        </tr>
    </tfoot>
</table>
</p>

<!-- jQuery -->
<script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>
<!-- Morris.js charts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/raphael/2.1.0/raphael-min.js"></script>
<script src="<?=base_url('plugins/morris/morris.min.js');?>"></script>
<script>
    var datapie = <?=$datapie?>;   

    $(document).ready(function () {
        var donut = new Morris.Donut({
            element: 'sales-chart',
            resize: true,
            colors: ["#3c8dbc", "#f56954", "#00a65a"],
            data: datapie,
            hideHover: 'auto'
        });
    });
</script>