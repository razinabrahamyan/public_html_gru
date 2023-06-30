<?php namespace App\Controllers;

/*
Основной контроллер телеграм бота
 */
class Telegram extends BaseController
{	
	public function index() {
		return view('welcome_message');
	}

	/*
	Получить данные от сервера telegram
	 */
	public function hook() {
		return $this->TelegramModel->hook();
	}

	/*
	Задать вебхук
	 */
	public function set() {
		if (!$res = $this->TelegramModel->setWebHook()) {
			echo 'Не удалось выполнить подключение к API Telegram, подробности о вебхуках: https://core.telegram.org/bots/webhooks';
			return TRUE;
		}

        if ($res->ok) {
            echo "<h1>Бот успешно подключен и работает с домена ".$_SERVER['HTTP_HOST']."</h1>";
            echo "Результат подключения: ".$res->description."<br>";
            $res = $this->TelegramModel->getMe();
            if ($res->ok) {
                echo "Название бота: ".$res->result->first_name." <a href='http://tg-me.ru/".$res->result->username."'>@".$res->result->username."</a><br>";
            }
            $res = $this->TelegramModel->getWebhookInfo();
            if ($res->ok) {
                echo "Запросы с Telegram будут приходить на: ".$res->result->url."<br>";
            }
            
        } else {
            echo 'Ошибка при подключении: '.$res->description;
        }

        return TRUE;
	}
}
