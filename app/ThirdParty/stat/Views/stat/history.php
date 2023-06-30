
<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-check"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<div class="pull-right">
	<p>
    <a class="btn btn-default btn-flat" title="По часам" href="<?=base_url('stat/hours')?>"><i class="far fa-clock"></i> По часам</a> 
    <a class="btn btn-default btn-flat" title="По дням" href="<?=base_url('stat/days')?>"><i class="fas fa-calendar-alt"></i> По дням</a> 
    </p>           
</div>

<!-- график -->
<div class="row">
<div class="col-md-12">
    <style>
        #chartdiv {
            width	: 100%;
            height	: 500px;
        }																	
    </style>

    <!-- Resources -->
	<script src="<?=base_url('plugins/amcharts/jquery-2.2.3.min.js');?>"></script>
    <script src="<?= base_url('plugins/amcharts/amcharts.js'); ?>"></script>
    <script src="<?= base_url('plugins/amcharts/serial.js'); ?>"></script>
    <script src="<?= base_url('plugins/amcharts/plugins/export/export.min.js'); ?>"></script>
    <link rel="stylesheet" href="<?= base_url('plugins/amcharts/plugins/export/export.css'); ?>">
    <script src="<?= base_url('plugins/amcharts/themes/light.js'); ?>"></script>

    <!-- Chart code -->
    <script>
        //src https://www.amcharts.com/demos/multiple-value-axes/
        var chartData = [<?php 
                $i = 0;
                foreach($data_array['data'] as $data){
                    if ($i > 0) {
                        echo ", ";
                    }
                    echo json_encode($data);
                    $i++;
                }
            ?>];

        var chart = AmCharts.makeChart("chartdiv", {
            "type": "serial",
            "theme": "light",
            "legend": {
                "useGraphSettings": true
            },
            "dataProvider": chartData,
            "synchronizeGrid": true,
            "valueAxes": [{
                    "id": "v1",
                    "axisColor": "#000000",
                    "axisThickness": 2,
                    "axisAlpha": 1,
                    "position": "left"
                }],
            "graphs": [<?php 
                    $i = 0;
                    foreach($data_array['graphs'] as $graph){
                        if ($i > 0) {
                            echo ", ";
                        }
                        echo json_encode($graph);
                        $i++;
                    }
                ?>],
            "chartScrollbar": {},
            "chartCursor": {
                "cursorPosition": "mouse"
            },
            "categoryField": "date",
            "categoryAxis": {
                "parseDates": true,
                "axisColor": "#DADADA",
                "minorGridEnabled": true
            }
        });

        chart.addListener("dataUpdated", zoomChart);
        zoomChart();
        function zoomChart() {
            chart.zoomToIndexes(chart.dataProvider.length - 20, chart.dataProvider.length - 1);
        }

    </script>

    <!-- HTML -->
    <div id="chartdiv"></div>
</div>
</div>



<!-- таблица -->
<script>var ajax_data = '<?=base_url('stat/index_')?>';</script>
<table id="ajax_table" class="table table-bordered table-striped dataTable">
	<thead>
		<tr>
			<th>Дата</th>
			<th>Текст/Кнопка</th>
			<th>Пользователь</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<th>Дата</th>
			<th>Текст/Кнопка</th>
			<th>Пользователь</th>
		</tr>
	</tfoot>
</table>

