<?php namespace Admin\Models;

/**
 * Name:    Модель для работы с кроном
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 * 
 * @example
 * $this->CronModel = new \Admin\Models\CronModel();
 * $res = $this->CronModel->cron_to_string("0 12,18 * * *");
   var_dump($res);
   // "каждый день в 12,18:0"

 */
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

class CronModel
{
	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
        $this->cron = new \Admin\Libraries\Cron();
	}


    /*
     * Преобразует формат крон в человеческий вид
     * @docs https://github.com/arnapou/jqcron
     */

    public function cron_to_string($string = NULL) {
        if (empty($string)) {
            return FALSE;
        }
        $this->cron->setCron($string);
        return $this->cron->getText();
    }

    /*
     * Парсинг крон выражения
     * Запустить сейчас?
     * @example 
     * if ($this->cron_is_now($string)){
     *  //тут действия 
     * }
     * $date = 'January 5, 2012'; - будет ли выполнено в заданную дату
     * @source https://github.com/mtdowling/cron-expression
     * @docs http://mtdowling.com/blog/2012/06/03/cron-expressions-in-php/
     */

    public function cron_is_now($string, $date = FALSE) {
        if (empty($string)) {
            return FALSE;
        }
        $cron = \Cron\CronExpression::factory($string);
        if (!$date) {
            return $cron->isDue();
        }
        return $cron->isDue($date);
    }

    /*
     * Парсинг крон выражения
     * Получить дату следующего запуска
     * @params $count - количество следующих дат
     * если не указано - то просто следующую дату
     * 
     * @source https://github.com/mtdowling/cron-expression
     * @docs http://mtdowling.com/blog/2012/06/03/cron-expressions-in-php/
     */

    public function cron_next_date($string = NULL, $count = FALSE) {
        if (empty($string)) {
            return FALSE;
        }
        $cron = Cron\CronExpression::factory($string);

        if (!$count) {   //получить одну следующую дату     
            return $cron->getNextRunDate()->format('Y-m-d H:i:s');
        }

        //вернуть массив следующих дат запуска
        $return = array();
        foreach ($cron->getMultipleRunDates($count) as $date) {
            $return[] = $date->format('Y-m-d H:i:s');
        }
        return $return;
    }

    /*
     * Парсинг крон выражения
     * Получить дату прошлого запуска
     * @params $count - количество следующих дат
     * если не указано - то просто следующую дату
     * 
     * @source https://github.com/mtdowling/cron-expression
     * @docs http://mtdowling.com/blog/2012/06/03/cron-expressions-in-php/
     */

    public function cron_last_date($string = NULL, $count = FALSE) {
        if (empty($string)) {
            return FALSE;
        }
        $cron = Cron\CronExpression::factory($string);

        if (!$count) {   //получить одну следующую дату     
            return $cron->getPreviousRunDate()->format('Y-m-d H:i:s');
        }

        //вернуть массив следующих дат запуска
        $return = array();
        foreach ($cron->getMultipleRunDates($count, 'now', TRUE) as $date) {
            $return[] = $date->format('Y-m-d H:i:s');
        }
        return $return;
    }
}
