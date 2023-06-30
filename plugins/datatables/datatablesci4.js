 $(function () {
    //http://kronus.me/2012/11/jquery-datatables-%D1%84%D0%B8%D0%BB%D1%8C%D1%82%D1%80%D0%B0%D1%86%D0%B8%D1%8F-%D0%B4%D0%B0%D0%BD%D0%BD%D1%8B%D1%85/
    $("#example1").DataTable({
      "stateSave": true,
      "paging": true,
      "lengthChange": true,
      "searching": true,
      "ordering": true,
      "info": true,
      "autoWidth": true,
      "oLanguage": {
        "sProcessing": "Подождите...",
        "sLengthMenu": "Показать _MENU_ записей",
        "sZeroRecords": "Записи отсутствуют.",
        "sInfo": "Записи с _START_ до _END_ из _TOTAL_ записей",
        "sInfoEmpty": "Записи с 0 до 0 из 0 записей",
        "sInfoFiltered": "(отфильтровано из _MAX_ записей)",
        "sInfoPostFix": "",
        "sSearch": "Поиск:",
        "sUrl": "",
        "oPaginate": {
          "sFirst": "Первая",
          "sPrevious": "Предыдущая",
          "sNext": "Следующая",
          "sLast": "Последняя"
        }
      }
      ,responsive: true
    });
    
    $("#example3").DataTable({
      "stateSave": true,
      "paging": true,
      "lengthChange": true,
      "searching": true,
      "ordering": true,
      "info": true,
      "autoWidth": true,
      "oLanguage": {
        "sProcessing": "Подождите...",
        "sLengthMenu": "Показать _MENU_ записей",
        "sZeroRecords": "Записи отсутствуют.",
        "sInfo": "Записи с _START_ до _END_ из _TOTAL_ записей",
        "sInfoEmpty": "Записи с 0 до 0 из 0 записей",
        "sInfoFiltered": "(отфильтровано из _MAX_ записей)",
        "sInfoPostFix": "",
        "sSearch": "Поиск:",
        "sUrl": "",
        "oPaginate": {
          "sFirst": "Первая",
          "sPrevious": "Предыдущая",
          "sNext": "Следующая",
          "sLast": "Последняя"
        }
      },
        //сортировка первой колонки по убыванию
        "order": [[0, "desc"]],
        responsive: true
      });


    if (typeof ajax_data != 'undefined') {
        /*
         * С постраничной навигацией на Ajax
         */
         $("#ajax_table").DataTable({
          "processing": true,
          "serverSide": true,
          "ajax": {
            "url": ajax_data,
            "type": 'POST'
          },

          "stateSave": true,
          "paging": true,
          "lengthChange": true,
          "searching": true,
          "ordering": true,
          "info": true,
          "autoWidth": true,
          "oLanguage": {
            "sProcessing": "Подождите...",
            "sLengthMenu": "Показать _MENU_ записей",
            "sZeroRecords": "Записи отсутствуют.",
            "sInfo": "Записи с _START_ до _END_ из _TOTAL_ записей",
            "sInfoEmpty": "Записи с 0 до 0 из 0 записей",
            "sInfoFiltered": "(отфильтровано из _MAX_ записей)",
            "sInfoPostFix": "",
            "sSearch": "Поиск:",
            "sUrl": "",
            "oPaginate": {
              "sFirst": "Первая",
              "sPrevious": "Предыдущая",
              "sNext": "Следующая",
              "sLast": "Последняя"
            }
          },
            //сортировка первой колонки по убыванию
            "order": [[0, "desc"]],
            responsive: true
          });
    } //if


  });