
<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-check"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<div class="pull-right">
	<p>
        <a class="btn btn-default btn-flat" title="История" href="<?=base_url('stat')?>"><i class="far fa-chart-bar"></i> История</a> 
        <a class="btn btn-default btn-flat" title="По дням" href="<?=base_url('stat/days')?>"><i class="fas fa-calendar-alt"></i> По дням</a> 
    </p>           
</div>

<!-- график -->
<div class="row">
    <div class="col-md-12">
        <div class="chart">
            <canvas id="myChart" style="height:230px"></canvas>
        </div>
    </div>
</div>

<script src="<?= base_url('plugins/chart.js/Chart.min.js'); ?>"></script>
<script>
    var ctx = document.getElementById('myChart');
    var myChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [<?php 
                $i = 0;
                foreach ($data_stat as $item) {
                    if ($i > 0) {
                        echo ",";
                    }
                    echo "'".$item['hour']."'"; 
                    $i++;
                }
            ?>],
            datasets: [{
                label: 'действия',
                data: [<?php 
                $i = 0;
                foreach ($data_stat as $item) {
                    if ($i > 0) {
                        echo ",";
                    }
                    echo "'".$item['count']."'"; 
                    $i++;
                }
            ?>],
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
  </script>