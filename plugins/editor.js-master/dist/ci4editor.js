//HTML теги поддерживаемые телеграм - https://core.telegram.org/bots/api#html-style
//как писать плагины https://editorjs.io/the-first-plugin
//сжиматель иконок https://www.iloveimg.com/resize-image/resize-svg
var editor = new EditorJS({
	holder: 'editorjs',
	autofocus: true,
	logLevel: 'ERROR',
		placeholder: 'Напишите текст',
		
		//инструменты
		tools: {
			delimiter: Delimiter,
			
			preformated: {
	          class:  Preformated,
	          shortcut: 'CMD+SHIFT+P'
	        },
			underline: {
	          class:  Underline,
	          shortcut: 'CMD+SHIFT+U'
	        },
			strike: {
	          class:  Strike,
	          shortcut: 'CMD+SHIFT+S'
	        },
			inlineCode: {
	          class: InlineCode,
	          shortcut: 'CMD+SHIFT+C'
	        },
			// linkTool: LinkTool
		},

		//данные из БД
		data: {
			blocks: blocks
		},

		onReady: function(){
			
		},

       //при изменении данных
       onChange: function() {
       	editor.save().then((savedData) => {
       			//анимация сохранения
       			Pace.restart();

	       		//сохраняем на сервере
	       		$.ajax({
	       			type: 'POST',
	       			url: $('#editorjs').attr('url'),
	       			data: savedData,
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
       }
   });