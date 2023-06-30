<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="x-ua-compatible" content="ie=edge">

  <title><?= $appName ?></title>

  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/e2eeb5e2ac.js"></script>
  
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  
  <!-- Google Font: Source Sans Pro -->
  <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
  
  <!-- DataTables -->
  <link rel="stylesheet" href="<?=base_url('/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css');?>">
  <link rel="stylesheet" href="<?=base_url('/plugins/datatables-responsive/css/responsive.bootstrap4.min.css');?>">
  
  <!-- tooltipster-master -->
  <link rel="stylesheet" type="text/css" href="<?= base_url('plugins/tooltipster-master/dist/css/tooltipster.bundle.min.css'); ?>" />
  
  <!-- выбор emoji -->
  <link href="<?= base_url('/plugins/emoji-picker-master/lib/css/emoji.css'); ?>" rel="stylesheet">
  
  <!-- preloader -->
  <link rel="stylesheet" href="<?= base_url('/plugins/pace-progress/themes/blue/pace-theme-minimal.css'); ?>">
  
  <!-- Tempusdominus Bbootstrap 4 -->
  <link rel="stylesheet" href="<?= base_url('/plugins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css'); ?>">
  
  <!-- select2 -->
  <link rel="stylesheet" href="<?= base_url('/plugins/select2/css/select2.min.css'); ?>">
  <link rel="stylesheet" href="<?= base_url('/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css'); ?>">
  
  <!-- Bootstrap4 Duallistbox -->
  <link rel="stylesheet" href="<?= base_url('/plugins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css'); ?>">

  <!-- Theme style -->
  <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css'); ?>">
  
  <!-- Google Font -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,600,700,300italic,400italic,600italic">
</head>
<body class="hold-transition sidebar-mini">
  <div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand bg-white navbar-light border-bottom">
      <!-- Left navbar links -->
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link" data-widget="pushmenu" href="#"><i class="fa fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <a href="<?=base_url('/admin');?>" class="nav-link">Главная</a>
        </li>
      </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">

      <!-- Sidebar -->
      <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
          <div class="image">
            <img src="<?=$user_data->grav_url;?>" class="img-circle elevation-2" alt="User Image">
          </div>
          <div class="info">
            <a href="#" class="d-block"><?= $userFirstName ?> <?= $userLastName ?> <?=$is_admin ? '<i title="Администратор" class="fas fa-crown"></i>' : ""?></a>
            <a href="<?=base_url('/auth/logout');?>" class="d-block"><span class="right badge badge-danger">выйти</span></a>
          </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
          <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
           with font-awesome or any other icon font library -->
           <?= $leftMenu ?>


         </ul>
       </nav>
       <!-- /.sidebar-menu -->
     </div>
     <!-- /.sidebar -->
   </aside>

   <!-- Content Wrapper. Contains page content -->
   <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1 class="m-0 text-dark"><?= $pageTitle ?></h1>
          </div><!-- /.col -->
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <?=$Breadcrumb?>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <div class="content">
      <div class="container-fluid">
        <?= $body ?>
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
    <div class="p-3">
      <h5>Title</h5>
      <p>Sidebar content</p>
    </div>
  </aside>
  <!-- /.control-sidebar -->

</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->

<!-- jQuery -->
<script src="<?=base_url('/plugins/jquery/jquery.min.js');?>"></script>
<!-- Bootstrap 4 -->
<script src="<?=base_url('/plugins/bootstrap/js/bootstrap.bundle.min.js');?>"></script>
<!-- Select2 -->
<script src="<?=base_url('/plugins/select2/js/select2.full.min.js');?>"></script>
<!-- AdminLTE App -->
<script src="<?=base_url('/assets/js/adminlte.min.js');?>"></script>
<!-- DataTables https://datatables.net/extensions/rowreorder/examples/initialisation/responsive.html -->
<script src="<?=base_url('/plugins/datatables/jquery.dataTables.min.js');?>"></script>
<script src="<?=base_url('/plugins/datatables-rowreorder/js/dataTables.rowReorder.min.js');?>"></script>
<script src="<?=base_url('/plugins/datatables-responsive/js/dataTables.responsive.min.js');?>"></script>
<script src="<?=base_url('/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js');?>"></script>
<script src="<?=base_url('/plugins/datatables-responsive/js/responsive.bootstrap4.min.js');?>"></script>
<script src="<?=base_url('/plugins/datatables/datatablesci4.js');?>"></script>
<!-- tooltipster-master -->
<script src="<?=base_url('/plugins/tooltipster-master/dist/js/tooltipster.bundle.min.js');?>"></script>

<!-- https://github.com/OneSignal/emoji-picker -->
<script src="<?=base_url('/plugins/emoji-picker-master/lib/js/config.js');?>"></script>
<script src="<?=base_url('/plugins/emoji-picker-master/lib/js/util.js');?>"></script>
<script src="<?=base_url('/plugins/emoji-picker-master/lib/js/jquery.emojiarea.js');?>"></script>
<script src="<?=base_url('/plugins/emoji-picker-master/lib/js/emoji-picker.js');?>"></script>

<script src="<?=base_url('/plugins/pace-progress/pace.min.js');?>"></script>

<!-- page script -->
<script>
  $(document).ready(function () {

    //Initialize Select2 Elements
    $('.select2').select2();

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    });

    //всплывающие подсказки
    $(':not([title=""])').tooltipster();

    //выбор иконок в поле ввода
    window.emojiPicker = new EmojiPicker({
      emojiable_selector: '[data-emojiable=true]',
      assetsPath: '<?=base_url('/plugins/emoji-picker-master/lib/img');?>',
      popupButtonClasses: 'fa fa-smile-o'
    });
    window.emojiPicker.discover();

  });
</script>
</body>
</html>
