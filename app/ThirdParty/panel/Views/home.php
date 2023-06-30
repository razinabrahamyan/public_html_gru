<!-- Small boxes (Stat box) -->
<div class="row">
  <div class="col-lg-3 col-12">
    <!-- small box -->
    <div class="small-box bg-info">
      <div class="inner">
        <h3><?=$orders?></h3>

        <p>Заказы</p>
      </div>
      <div class="icon">
        <i class="ion ion-bag"></i>
      </div>
      <a href="<?=base_url('orders')?>" class="small-box-footer">подробнее <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <!-- ./col -->
  <div class="col-lg-3 col-12">
    <!-- small box -->
    <div class="small-box bg-success">
      <div class="inner">
        <h3><?=$payed?></h3>

        <p>Оплаты</p>
      </div>
      <div class="icon">
        <i class="ion ion-stats-bars"></i>
      </div>
      <span class="small-box-footer"><?=$sum?> <?=$currency_name?></span>
    </div>
  </div>
  <!-- ./col -->
  <div class="col-lg-3 col-12">
    <!-- small box -->
    <div class="small-box bg-warning">
      <div class="inner">
        <h3><?=$users?></h3>

        <p>Пользователи</p>
      </div>
      <div class="icon">
        <i class="ion ion-person-add"></i>
      </div>
      <a href="<?=base_url('admin/users')?>" class="small-box-footer">подробнее <i class="fas fa-arrow-circle-right"></i></a>
    </div>
  </div>
  <!-- ./col -->
  <div class="col-lg-3 col-12">
    <!-- small box -->
    <div class="small-box bg-danger">
      <div class="inner">
        <h3><?=$conv?> <sup style="font-size: 20px">%</sup></h3>

        <p>Конверсия</p>
      </div>
      <div class="icon">
        <i class="ion ion-pie-graph"></i>
      </div>
      <span class="small-box-footer"><?=$users_payed?> клиент</span>
    </div>
  </div>
  <!-- ./col -->
</div>

<div class="row">
  <div class="col-12">

    <div id="funnel" style="width: 400px; height: 300px;"></div>
    <script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>
    <script type="text/javascript" src="<?=base_url('/plugins/amcharts/amcharts.js');?>"></script>
    <script type="text/javascript" src="<?=base_url('/plugins/amcharts/funnel.js');?>"></script>
    <script>
        var chart;
        var data = [
            {
                "title": "Старт",
                "value": <?=$users?>,
                "color": "#00bcd4"
            },       
            {
                "title": "Заказы (<?=$conv_orders?>%)",
                "value": <?=$users_orders?>,
                "color": "#8bc34a"
            },
            {
                "title": "Оплаты (<?=$conv?>%)",
                "value": <?=$users_payed?>,
                "color": "#e65100"
            }
        ];
        AmCharts.ready(function () {
            chart = new AmCharts.AmFunnelChart();
            // chart.addTitle("Воронка продаж", 16);    
            chart.titleField = "title";
            chart.balloon.cornerRadius = 0;
            chart.marginRight = 220;
            chart.marginLeft = 15;
            chart.labelPosition = "right";
            chart.funnelAlpha = 0.9;
            chart.valueField = "value";
            chart.dataProvider = data;
            chart.startX = 0;
            chart.balloon.animationTime = 0.2;
            chart.neckWidth = "40%";
            chart.startAlpha = 0;
            chart.neckHeight = "30%";
            chart.balloonText = "[[title]]:<b>[[value]] чел.</b>";
            chart.angle = 0; //40
            chart.depth3D = 100;
            chart.creditsPosition = "top-right";
            chart.colorField = "color";
            chart.write("funnel");
        });
    </script>  

  </div>
</div>