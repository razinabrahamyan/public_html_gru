
<?php if (!empty($message)) {?>
	<div class="alert alert-success alert-dismissible">
		<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
		<h5><i class="icon fas fa-check"></i> ВНИМАНИЕ!</h5>
		<?=$message;?>
	</div>
<?php } ?>

<?=form_open(uri_string());?>
<div class="row">
  <div class="col-md-12">
    <div class="card card-default">
      <div class="card-header">
        <h3 class="card-title">Выборка по <?=count($products)?>-м продуктам</h3>
        <div class="card-tools">
          <button type="button" class="btn btn-tool" data-card-widget="collapse" data-toggle="tooltip" title="Collapse">
            <i class="fas fa-minus"></i></button>
        </div>
    </div>
    <div class="card-body">
      <div class="row">

        <div class="col-md-12">
          <div class="row">
            <div class="col-md-5">
              <div class="form-group">
                <!-- <label>Date and time range:</label> -->
                <div class="input-group">
                  <div class="input-group-prepend">
                    <button type="submit" class="btn btn-success">Посмотреть</button>
                </div>
                <input name="daterange" value="<?=$daterange?>" type="text" class="form-control float-right" id="daterangepicker">
            </div>
        </div>
    </div>
</div>

<!-- daterange picker -->
<link rel="stylesheet" href="<?=base_url('plugins/daterangepicker/daterangepicker.css');?>">
<script src="<?=base_url('plugins/jquery/jquery.min.js');?>"></script>
<script src="<?=base_url('plugins/moment/moment-with-locales.min.js');?>"></script>
<script src="<?=base_url('plugins/daterangepicker/daterangepicker.js');?>"></script>
<script src="<?=base_url('plugins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js');?>"></script>

<script>
    jQuery(document).ready(function($) {
        //docs http://www.daterangepicker.com/#config
        $('#daterangepicker').daterangepicker({
          dateFormat: 'YYYY-MM-DD',
          timeFormat: 'HH:mm',
          timePicker: true,
          timePicker24Hour: true,
          pick12HourFormat: false,
          timePicker: true,
          autoApply: true,
          drops: "auto",
          locale: {
            locale: 'ru',
            format: 'YYYY-MM-DD HH:mm',
            separator: ' / ',
            applyLabel: "Применить",
            cancelLabel: "Отмена",
            "daysOfWeek": [
            "ПН",
            "ВТ",
            "СР",
            "ЧТ",
            "ПТ",
            "СБ",
            "ВС"
            ],
            "monthNames": [
            "Январь",
            "Февраль",
            "Март",
            "Апрель",
            "Май",
            "Июнь",
            "Июль",
            "Август",
            "Сентябрь",
            "Октябрь",
            "Ноябрь",
            "Декабрь"
            ],
        }
    }).on('apply.daterangepicker', function (ev, picker) {
      $(this).val(picker.startDate.format('YYYY-MM-DD HH:mm') + ' / ' + picker.endDate.format('YYYY-MM-DD HH:mm'));
  });
});
</script>
</div>

</div>

<div class="row">
    <div class="col-md-12">
      <div class="form-group">
        <!-- <label>Продукт (ы)</label> -->
        <select name="products[]" multiple="multiple"  class="form-control select2 " data-placeholder="Одну или несколько записей" style="width: 100%;">
          <?php foreach ($items as $item): ?>
            <option value="<?= $item['id'] ?>"
              <?php foreach ($products as $id_product){
                if ( $id_product == $item['id']) {
                  echo "selected";
              }
          } ?>
          ><?= $item['name'] ?></option>
      <?php endforeach; ?>
  </select>
</div>
</div>
</div>

<script src="<?=base_url('plugins/jquery/jquery.min.js');?>"></script>
<script src="<?=base_url('plugins/moment/moment-with-locales.min.js');?>"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/tempusdominus-bootstrap-4/5.0.0-alpha14/js/tempusdominus-bootstrap-4.min.js"></script>

<script>
  jQuery(document).ready(function($) {
              //https://tempusdominus.github.io/bootstrap-4/Usage/
              $('#datetimepicker1').datetimepicker({
                locale: 'ru',
                format: 'YYYY-MM-DD HH:mm', //https://momentjs.com/docs/#/displaying/format/
            });

              $('#datetimepicker2').datetimepicker({
                useCurrent: false,
                locale: 'ru',
                format: 'YYYY-MM-DD HH:mm', //https://momentjs.com/docs/#/displaying/format/
            });

              $("#datetimepicker1").on("change.datetimepicker", function (e) {
                $('#datetimepicker2').datetimepicker('minDate', e.date);
            });
              $("#datetimepicker2").on("change.datetimepicker", function (e) {
                $('#datetimepicker1').datetimepicker('maxDate', e.date);
            });

          });
      </script>

  </div>
  <!-- /.card-body -->
</div>
<!-- /.card -->
</div>
</div>
<?=form_close(); ?>

<!-- график -->
<div class="row">
    <div class="col-md-12">
        <style>
            #chartdiv {
                width	: 100%;
                height	: 500px;
            }																	
        </style>

        <script type="text/javascript" src="<?=base_url('plugins/jquery/jquery.min.js');?>"></script>
        <script type="text/javascript" src="<?=base_url('plugins/amcharts4/core.js');?>"></script>
        <script type="text/javascript" src="<?=base_url('plugins/amcharts4/charts.js');?>"></script>
        <script type="text/javascript" src="<?=base_url('plugins/amcharts4/themes/animated.js');?>"></script>

        <!-- Chart code -->
        <script>
            am4core.ready(function() {

            // Themes begin
            am4core.useTheme(am4themes_animated);
            // Themes end

            // Create chart instance
            var chart = am4core.create("chartdiv", am4charts.XYChart);

            // Add data
            chart.data = [ <?php 
                $i = 0;
                foreach($data_array as $item){
                    if ($i > 0) {
                        echo ", ";
                    }
                    echo json_encode($item);
                    $i++;
                }
            ?>];

            // Create axes
            var categoryAxis = chart.xAxes.push(new am4charts.CategoryAxis());
            categoryAxis.dataFields.category = "day";
            categoryAxis.title.text = "Статистика c <?=$date_start?> по <?=$date_finish?>";
            categoryAxis.renderer.grid.template.location = 0;
            categoryAxis.renderer.minGridDistance = 20;
            categoryAxis.renderer.cellStartLocation = 0.1;
            categoryAxis.renderer.cellEndLocation = 0.9;

            var  valueAxis = chart.yAxes.push(new am4charts.ValueAxis());
            valueAxis.min = 0;
            valueAxis.title.text = "Количество";

            // Create series
            function createSeries(field, name, stacked) {
              var series = chart.series.push(new am4charts.ColumnSeries());
              series.dataFields.valueY = field;
              series.dataFields.categoryX = "day";
              series.name = name;
              series.columns.template.tooltipText = "{name}: [bold]{valueY}[/]";
              series.stacked = stacked;
              series.columns.template.width = am4core.percent(95);
            }

            createSeries("start", "СТАРТ", false);
            createSeries("order", "Заказы", false);
            createSeries("pay", "Оплаты", false);

            // Add legend
            chart.legend = new am4charts.Legend();

            }); // end am4core.ready()
</script>

<!-- HTML -->
<div id="chartdiv"></div>

</div>
</div>
