<?php 

/**
 * Name:    Модель для работы с yandex geocoder
 *
 * Created:  02.08.2021
 *
 * Description:  
 *
 * Requirements: PHP 7.2 or above
 *
 * @author     Krotov Roman <tg: @KrotovRoman>
 * @docs https://tech.yandex.ru/maps/geocoder/doc/desc/examples/geocoder_examples-docpage/
 */
namespace Yandexgeo\Models;

use CodeIgniter\Model;
use \CodeIgniter\Database\ConnectionInterface;


/**
 * Class YandexgeoModel
 */
class YandexgeoModel
{

	/**
	 * Constructor
	 *
	 * @return void
	 */
	public function __construct() {
        $this->SettingsModel = new \Admin\Models\SettingsModel();
        $settings = $this->SettingsModel->all(TRUE);
        foreach ($settings as $settings_) {
            $this->{$settings_['name']} = trim($settings_['value']);
        }
    }

    /*
      Получить адрес в текстовом виде по координатам
      @params 
      $latitude - широта
      $longitude - долгота
      @docs https://tech.yandex.ru/maps/geocoder/doc/desc/examples/geocoder_examples-docpage/

      @example 
      $this->YandexgeoModel = new \Yandexgeo\Models\YandexgeoModel();
      $res = $this->YandexgeoModel->to_text($longitude, $latitude);
       */
      public function to_text($latitude, $longitude) {
        if (empty($this->yamap)) {
          return FALSE;
        }
        $data = [];
        $data['geocode'] = $latitude.",".$longitude;
        $data['apikey'] = $this->yamap;
        $data['format'] = "json";
        $data = http_build_query($data);

        try {
            $return = json_decode(file_get_contents("https://geocode-maps.yandex.ru/1.x/?". $data));
        } catch (\Exception $e) {
            log_message('error', print_r($e->getMessage(), TRUE));
            return FALSE;
        }

        if (!isset($return->response->GeoObjectCollection->featureMember[0]->GeoObject->metaDataProperty->GeocoderMetaData->text)) {
          return FALSE;
        }

          $result['formatted_address'] = $return->response->GeoObjectCollection->featureMember[0]->GeoObject->metaDataProperty->GeocoderMetaData->Address->formatted;
          $result['country_code'] = $return->response->GeoObjectCollection->featureMember[0]->GeoObject->metaDataProperty->GeocoderMetaData->Address->country_code;

          $components = $return->response->GeoObjectCollection->featureMember[0]->GeoObject->metaDataProperty->GeocoderMetaData->Address->Components;
          foreach($components as $component) {
              if ($component->kind == "locality") {
                $component->kind = "city";
            } else if ($component->kind == "house") {
                $component->kind = "number";
            }
            $result[$component->kind] = $component->name;
        }
        if (isset($return->response->GeoObjectCollection->featureMember[0]->GeoObject->metaDataProperty->GeocoderMetaData->Address->postal_code)) {
          $result['postal_code'] = $return->response->GeoObjectCollection->featureMember[0]->GeoObject->metaDataProperty->GeocoderMetaData->Address->postal_code;
      } else {
          $result['postal_code'] = 0;
      }

      return $result;
    }
}
