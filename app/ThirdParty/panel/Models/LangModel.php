<?php namespace Admin\Models;

/**
 * Name:    Модель для работы с кнопками бота
 *
 * Created:  03.04.2020
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 */
use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Class ButtonsModel
 */
class LangModel
{
	/**
	 * Database object
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db;
	protected $config;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		$this->db = \Config\Database::connect();
        $this->ButtonsModel = new \Admin\Models\ButtonsModel();
        $this->PagesModel = new \Admin\Models\PagesModel();
    }

	/**
	 * Getter to the DB connection used by Ion Auth
	 * May prove useful for debugging
	 *
	 * @return object
	 */
	public function db() {
		return $this->db;
	}

    /*
    * Экспорировать в эскель для удобства перевода
    *
    * @param bool $download - TRUE - скачать щас, иначе вернет путь к файлу на сервере
    * @docs https://phpspreadsheet.readthedocs.io/en/latest/
     */
    public function export($table = "menu_buttons", $download = TRUE) {
        $filename = $table."-".date("d.m.Y").'.Xlsx';
        $languages = $this->languages(TRUE);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        //задаем автоширину всех колонок
        for ($i = 1; $i <= (count($languages) + 1); $i++) {
            $sheet->getColumnDimensionByColumn($i)->setAutoSize(TRUE);
        }

        $sheet->setCellValue('A1', 'id'); //кроме первого столбка

        //задаем неограниченное количество столбцов
        $i = 2;
        foreach ($languages as $language) {
            $sheet->setCellValueByColumnAndRow($i, 1, $language['name']); 
            $i++;
        }

        $k = 2;
        $items = $this->db->table($table)->get()->getResultArray();
        foreach ($items as $item) {

            $sheet->setCellValueByColumnAndRow(1, $k, $item['id']);

            //получаем переводы
            if ($table == "menu_buttons") {
                $translates = $this->trans_btn($item['id']);
            } else {
                $translates = $this->trans_page($item['id']);
            }

            $i = 2;
            foreach ($translates as $translate) {
                if ($table <> "menu_buttons") {
                    $translate = $this->PagesModel->json_to_txt($translate);
                }
                $sheet->setCellValueByColumnAndRow($i, $k, $translate); 
                $i++;
            }
            $k++;
        }

        // return FALSE;

        $writer = new Xlsx($spreadsheet);

        if ($download) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            return $writer->save('php://output');
        } 

        $path = ROOTPATH.'/writable/uploads/'.$filename;
        $writer->save($path);
        return $path;
    }

    /*
    Определяем id кнопки на определенном языке
     */
    public function id_button(string $text, string $short = "ru") {
        $text = trim($text);
        // log_message('error', print_r($text, TRUE));
        // log_message('error', print_r(json_decode('\\u00a0'), TRUE));
        
        $text = json_encode($text);

        //ищем среди основных названий кнопки
        if ($short == "ru") {
            $button = $this->db
            ->table('menu_buttons')
            ->where("name", $text)
            ->limit(1)
            ->select('id')
            ->get()
            ->getRowArray();

            // log_message('error', print_r($this->db->showLastQuery(), TRUE));

            return isset($button['id']) ? $button['id'] : FALSE;
        }

        //ищем среди переводов
        $button = $this->db
        ->table('menu_buttons_translate')
        ->where('name', $text)
        ->where('short', $short)
        ->limit(1)
        ->select('id_button')
        ->get()
        ->getRowArray();

        return isset($button['id_button']) ? $button['id_button'] : FALSE;
    }

    /*
    Определяем язык пользователя
     */
    public function lang($chat_id) {
        $db = $this->db->table('users');
        $db->where('chat_id', $chat_id);
        $db->limit(1);
        $count = $db->countAllResults();
        if ($count <= 0) {
            return $this->default();
        }

        $language = $this->db
        ->table('users')
        ->where('chat_id', $chat_id)
        ->limit(1)
        ->select('lang')
        ->get()
        ->getRowArray();

        return empty($language['lang']) ? $this->default() : $language['lang'];
    }

    /*
    Сохранить перевод страницы
     */
    public function trans_page_set($id_page, $short, $text) {

        $db = $this->db->table('pages_translate');
        $db->where('id_page', $id_page);
        $db->where('short', $short);
        $db->limit(1);
        $count = $db->countAllResults();

        if ($count <= 0) {
            $db = $this->db->table('pages_translate');
            return $db->insert(['text' => $text, 'short' => $short, 'id_page' => $id_page]);
        } else {
            $db = $this->db->table('pages_translate');
            $db->where('id_page', $id_page);
            $db->where('short', $short);
            return $db->update(['text' => $text, 'short' => $short]);
        }
    }

    /*
    Получить перевод страницы
     */
    public function trans_page($id_page, $short = FALSE) {
        $this->PagesModel = new \Admin\Models\PagesModel();

        //если нужно получить перевод конкретной страницы
        if ($short !== FALSE) {
            $db = $this->db->table('pages_translate');
            $db->where('id_page', $id_page);
            $db->where('short', $short);
            if ($db->countAllResults() <= 0) {
                return json_encode([]);
            }
            
            $db = $this->db->table('pages_translate');
            $db->where('id_page', $id_page);
            $db->where('short', $short);
            $db->limit(1);
            $db->select('text');
            return $db->get()->getRow()->text;
        }

        $result = [];
        $result['ru'] = $this->PagesModel->get($id_page);

        //получаем все переводы этой страницы
        $db = $this->db->table('pages_translate');
        $db->where('id_page', $id_page);
        $translates = $db->get();
        
        $languages = $this->languages();
        foreach($languages as $lang) {
            if ($lang['short'] == "ru") {
                continue;
            }
            $result[$lang['short']] = NULL;
            foreach($translates->getResultArray() as $trans) {
                if ($trans['short'] == $lang['short'] AND !empty($trans['text'])) {
                    $result[$lang['short']] = $trans['text'];
                }
            }
        }

        return $result;
    }

    /*
    Сохранить перевод кнопки
     */
    public function trans_btn_set($id, $short, $value) {
        $value = json_encode(trim($value));

        $db = $this->db->table('menu_buttons_translate');
        $db->where('id_button', $id);
        $db->where('short', $short);
        $db->limit(1);
        $count = $db->countAllResults();

        if ($count <= 0) {
            $db = $this->db->table('menu_buttons_translate');
            return $db->insert(['name' => $value, 'short' => $short, 'id_button' => $id]);
        } else {
            $db = $this->db->table('menu_buttons_translate');
            $db->where('id_button', $id);
            $db->where('short', $short);
            return $db->update(['name' => $value, 'short' => $short]);
        }
    }

    /*
    Получить перевод кнопки
     */
    public function trans_btn($id_button, $short = FALSE) {

        if ($short !== FALSE) {
            $db = $this->db->table('menu_buttons_translate');
            $db->where('id_button', $id_button);
            $db->where('short', $short);
            $db->limit(1);
            if ($db->countAllResults() <= 0) {
                $button = $this->ButtonsModel->get($id_button);
                return $button['name'];
            }

            $db = $this->db->table('menu_buttons_translate');
            $db->where('id_button', $id_button);
            $db->where('short', $short);
            $db->limit(1);
            return json_decode($db->get()->getRow()->name);
        }

        $db = $this->db->table('menu_buttons_translate');
        $db->where('id_button', $id_button);
        $translates = $db->get();

        //кнопка по умолчанию на русском
        $button = $this->ButtonsModel->get($id_button);
        $result = [];
        $result['ru'] = $button['name'];

        $languages = $this->languages();
        foreach($languages as $lang) {
            if ($lang['short'] == "ru") {
                continue;
            }
            $result[$lang['short']] = NULL;
            foreach($translates->getResultArray() as $trans) {
                if ($trans['short'] == $lang['short'] AND !empty($trans['name'])) {
                    $result[$lang['short']] = json_decode($trans['name']);
                }
            }
        }

        return $result;
    }

    /*
    Сконвертировать в JSON названия
     */
    public function to_json($data, $field_ = "name") {
        foreach($data as $field => $value) {
            if (mb_stripos($field, $field_) !== FALSE) {
                $data[$field] = json_encode($value);
            }
        }
        return $data;
    }

    /*
    Сконвертировать из JSON названия
     */
    public function from_json($data, $field_ = "name") {
        foreach($data as $field => $value) {
            if (mb_stripos($field, $field_) !== FALSE) {
                $data[$field] = json_decode($value);
            }
        }
        return $data;
    }

    /*
    Получить данные языка
     */
    public function language($short) {
        $db = $this->db->table('languages');
        $db->where('short', $short);
        return $db->get()->getRowArray();
    }

    /*
    Получить активные языки
     */
    public function languages($all = FALSE) {
        $db = $this->db->table('languages');
        if (!$all) {
            $db->where('active', 1);
        }
        return $db->get()->getResultArray();
    }

    /*
    Сохраняем данные кнопки
     */
    public function set($data) {
        if (isset($data['is_default'])) {
            $this->db->table('languages')->where('id<>', $data['id'])->update(['is_default' => 0]);
        }
        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
        }

        $data['is_default'] = isset($data['is_default']) AND $data['is_default'] == "on";
        return $this->db->table('languages')->where('id', $data['id'])->update($data);
    }

    /*
    Получить язык по умолчанию
     */
    public function default() {
        $language = $this->db()->table('languages')
            ->where('is_default', 1)
            ->limit(1)
            ->get()
            ->getRowArray();
        return count($language) <= 0 ? "ru" : $language['short'];

    }

    /*
    Получить данные языка
     */
    public function get($id) {
        return $this->db()->table('languages')
        ->where('id', $id)
        ->limit(1)
        ->get()
        ->getRowArray();
    }

    /*
    Удалить язык со всеми переводами
     */
    public function delete($id) {
        $data = $this->get($id);
        if ($data['is_default'] > 0) {
            return FALSE; //невозможно удалить язык по умолчанию
        }
        
        //удаляем переводы
        $this->db()->table('menu_buttons_translate')->delete(['short' => $data['short']]);
        $this->db()->table('pages_translate')->delete(['short' => $data['short']]);

        return $this->db()->table('languages')->delete(['id' => $id]);
    }

    /*
    Добавление данных
     */
    public function add($data) {
        if ($this->db()->table('languages') ->where('short', $data['short'])->countAllResults() > 0) {
            return FALSE;
        }
        return $this->db->table('languages')->insert($data);
    }
}
