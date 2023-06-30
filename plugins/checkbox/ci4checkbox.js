$(document).ready(function () {
      $('[type="checkbox"]').change(function() {
        Pace.restart();

        //сохраняем на сервере
          $.ajax({
            type: 'POST',
            url: $(this).attr('url'),
            data: {
              'checked' : $(this).is(':checked')
            },
            dataType: 'json',
            success: function (data) {
              // если успешно сохранено - можно всплывающее сообщение
              console.log(data.message);
            },
            error: function (jqXHR, exception) {

              var msg = '';
              if (jqXHR.status === 0) {
                msg = 'Нет соединения с интернетом!';
              } else if (jqXHR.status == 404) {
                msg = 'Страница не найдена [404]';
              } else if (jqXHR.status == 500) {
                msg = 'Ошибка сервера [500]: '+ JSON.parse(jqXHR.responseText).message;
              } else if (exception === 'parsererror') {
                msg = 'Ошибка парсинга ответа сервера JSON '+ jqXHR.responseText;
              } else if (exception === 'timeout') {
                msg = 'Время ожидания вышло';
              } else if (exception === 'abort') {
                msg = 'Ajax запрос отклонен';
              } else {
                msg = 'Неизвестная ошибка.\n' + jqXHR.responseText;
              }

              console.log(msg);
              // alert(msg);
            }
          });
      });
  });