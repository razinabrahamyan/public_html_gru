<?php namespace Admin\Models;

/**
 * Name:    Модель для работы с пользователями
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
 * Class UsersModel
 */
class UsersModel
{
	/**
	 * Database object
	 *
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db;

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
		helper(['date']);
		$this->db = \Config\Database::connect();
        $this->ionAuth = new \IonAuth\Libraries\IonAuth();
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
    * Экспорировать в эскель
    *
    * @param bool $download - TRUE - скачать щас, иначе вернет путь к файлу на сервере
    * @docs https://phpspreadsheet.readthedocs.io/en/latest/
     */
    public function export($download = TRUE) {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setCellValue('A1', 'id');
        $sheet->setCellValue('B1', 'Имя');
        $sheet->setCellValue('C1', 'Фамилия');
        $sheet->setCellValue('D1', 'Username');
        $sheet->setCellValue('E1', 'Email');
        $sheet->setCellValue('F1', 'Телефон');

        //задаем автоширину колонок
        $sheet->getColumnDimension('A')->setAutoSize(TRUE);
        $sheet->getColumnDimension('B')->setAutoSize(TRUE);
        $sheet->getColumnDimension('C')->setAutoSize(TRUE);
        $sheet->getColumnDimension('D')->setAutoSize(TRUE);
        $sheet->getColumnDimension('E')->setAutoSize(TRUE);
        $sheet->getColumnDimension('F')->setAutoSize(TRUE);

        $users = $this->ionAuth->users('members')->resultArray();

        $i = 1;
        foreach ($users as $user) {
            $i++;
            $sheet->setCellValue('A'.$i, $user['id']);
            $sheet->setCellValue('B'.$i, json_decode($user['first_name']));
            $sheet->setCellValue('C'.$i, json_decode($user['last_name']));
            $sheet->setCellValue('D'.$i, $user['username']);
            $sheet->setCellValue('E'.$i, $user['email']);
            $sheet->setCellValue('F'.$i, $user['phone']);
        }

        $writer = new Xlsx($spreadsheet);
        
        $filename = date("dmY").'.Xlsx';
        
        if ($download) {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header("Content-Disposition: attachment; filename=\"{$filename}\"");
            $writer->save('php://output');
        } else {
            $path = ROOTPATH.'/writable/uploads/'.$filename;
            $writer->save($path);
            return $path;
        }
    }

    /*
    * Является ли этот пользователь единственным администратором в админке
    *
    * @return bool TRUE - это единственный пользователь с правами админа
     */
    public function is_uniq_admin(int $chat_id): bool {
        $data_user = $this->ionAuth->user($chat_id)->getRowArray();

        return $this->db->table('users_groups')
            ->where('user_id<>', $data_user['id']) //не текущий пользователь
            ->where('group_id', 1) //админ
            ->countAllResults() <= 0;
    }

    /*
    Удалить данные пользователя
     */
    public function delete($chat_id) {
        if ($this->is_uniq_admin($chat_id)) {
            return FALSE;
        }

        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $this->BalanceModel->delete_user($chat_id);
        
        $this->AffModel = new \Aff\Models\AffModel();
        $this->AffModel->delete_user($chat_id);

        //удаляем все заказы пользователя
        $this->OrderModel = new \Orders\Models\OrderModel();
        $this->OrderModel->delete_user($chat_id);

        return TRUE;
    }

    /*
    Проверить доступность к отделу для текущего пользователя
     */
    public function section_access($method) {

        //получаем id текущего раздела панели
        $id_item = $this->db->table('panel_sidebar')->where('method' , $method)->get()->getRow()->id;

        $access_arr = $this->db->table('panel_access')->where('id_item' , $id_item)->get()->getResultArray();
        foreach($access_arr as $access) {
            if ($this->ionAuth->inGroup($access['id_group'])) {
                return TRUE;
            }
        }

        return FALSE;
    }

    /*
    Получить всех пользователей которые не являются администраторами
     */
    public function noadmin() {
        $db = $this->db->table('users');
        $db->where('users.active', 1);
        $db->where('users.id>', 1);
        $items = $db->get();
        return $items->getResultArray();
    }

	 /*
     * Получить список с помощью ajax
     *   
     * @docs https://datatables.net/manual/server-side#Sent-parameters
     * @docs https://datatables.net/examples/server_side/simple.html
     * @docs https://datatables.net/examples/data_sources/server_side.html
     */

     public function users_($params, $filter = TRUE) {
        $db = $this->db->table('users');

        //поисковой фильтр
        if (!empty($params['search']['value'])) {
            $db->groupStart();
            $id = (int) trim($params['search']['value']);
            $time = human_to_unix(trim($params['search']['value']));
            if ($id > 0) {//если это число
                $db->where('users.id', $id);
                $db->orWhere('users.chat_id', $id);
            } else if ($time) {//если это дата                   
                $db->where('users.updated', trim($params['search']['value']));
            } else {//если это текст        
                $db->orLike('users.first_name', json_encode( trim($params['search']['value']) ));
                $db->orLike('users.last_name', json_encode(trim($params['search']['value']) ));
                $db->orLike('users.email', trim($params['search']['value']));
                $db->orLike('users.username', trim($params['search']['value']));
            }
            $db->groupEnd();
        }

        //список полей которые будут в таблице                
        $need_fields = array(
            'id',
            'first_name',
            'last_name',
            'email',
            'id',
            'active',
            'id'
        );

        //сортировка 
        $order_column = (int) $params['order'][0]['column'];
        $order_direction = $params['order'][0]['dir'];
        $dir = empty($order_direction) ? "desc" : $order_direction;
        $db->orderBy($need_fields[$order_column], $dir);

        if ($filter) {
            $limit = (int) $params['length'];
            $offset = (int) $params['start'];
            $items = $db->get($limit, $offset);
        } else {//если без фильтра - то общее количество записей
            return $db->countAllResults();
        }

        $this->BalanceModel = new \Balance\Models\BalanceModel();
        $this->SettingsModel = new \Admin\Models\SettingsModel();

        $return = [];
        foreach ($items->getResultArray() as $item) {
            $data = [];

            $data[] = "<a href='".base_url("admin/users/edit/" . $item['id'])."'>".($item['chat_id'] > 0 ? $item['chat_id'] : $item['id'])."</a>";
            $data_item =  "<a href='".base_url("admin/users/edit/" . $item['id'])."'>";
            $data_item.= json_decode($item['first_name'])." ".json_decode($item['last_name']);
            $data_item.="</a>";
            if (isset($item['username'])) {
                $data_item.=  " <a target='_blank' href='tg://resolve?domain=".$item['username']."'>";
                $data_item.= "@".$item['username'];
                $data_item.="</a>";
            }
            $data[] =$data_item;
            
            
            $balance = $this->BalanceModel->get($item['id']);
            $data[] = "<a href='".base_url("balance/items/" . $item['id'])."'>".number_format($balance, 2, ',', ' ')."</a>";
            $data[] = "<a href='".base_url("admin/users/edit/" . $item['id'])."'>".$item['email']."</a>";
            
            $data_item = "";
            $groups = $this->ionAuth->getUsersGroups($item['id']);
            $i = 0;
            foreach($groups->getResultArray() as $group) {
                if ($i > 0) {
                    $data_item .= ", ";
                }
                $data_item .= anchor('admin/users/edit/' . $item['id'], esc($group['description']));
                $i++;
                
            }
            $data[] = $data_item;


            $data[] = ($item['active']) ?
            anchor('admin/users/deactivate/' . $item['id'], "не забанен") :
            anchor('admin/users/activate/' . $item['id'], "заблокирован");
            
            $data_item = "";
            $data_item .= "<a title='Изменить' class='btn btn-success btn-flat' href='" . base_url("admin/users/edit/" . $item['id']) . "'><i class='fa fa-pencil'></i></a>";
            $data_item .= "<a title='Удалить' class='btn btn-danger btn-flat' href='" . base_url("admin/users/delete/" . $item['id']) . "'><i class='fa fa-trash'></i></a>";
            $data[] = $data_item;

            $return[] = $data;
        }

        return $return;
    }

}
