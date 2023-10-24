<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

// Подключаем API
require_once($_SERVER["DOCUMENT_ROOT"]."/local/API/settings.php");
require_once($_SERVER['DOCUMENT_ROOT']."/local/API/SearchDetailAPI.php");

session_start();
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Main\Type\Date;

use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\ActionFilter;

use \Bitrix\Main\Application;
use \Bitrix\Iblock\SectionTable;
use \Bitrix\Iblock\ElementTable;
use \Bitrix\Iblock\PropertyTable;
use Bitrix\Sale;

// Для работы с БД
use Bitrix\Main\ModuleManager;
use Bitrix\Main\Entity;

session_start();
Loader::includeModule('iblock');
Loader::includeModule("sale");

// Описание структуры БД
class PriceConstructorSettingsTable extends Entity\DataManager{
    public static function getTableName()
    {
        return 'PriceConstructorSettings';
    }
    public static function getMap()
    {
        return array(
            new Entity\IntegerField('ID', array('primary'=>true,'autocomplete'=>true)),
            
            new Entity\StringField('FILTER_GLOBAL_TYPE'),
            new Entity\StringField('FILTER_ITEMS'),

            new Entity\StringField('FILTER_TYPE'),
            new Entity\StringField('FIRTS_FIELD'),

            new Entity\StringField('MARKUP_TYPE'),
            new Entity\StringField('MARKUP_VALUE'),
        );
    }
}

// Подключение/создание бд если её нет
$connection = Application::getInstance()->getConnection();
            if(!$connection->isTableExists(PriceConstructorSettingsTable::getTableName()))
            PriceConstructorSettingsTable::getEntity()->createDbTable();

class FormComponent extends CBitrixComponent implements Controllerable{     

    public function randomString($length = 8) { 
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $charactersLength = strlen($characters); 
        $randomString = ''; 
        for ($i = 0; $i < $length; $i++) { 
            $randomString .= $characters[rand(0, $charactersLength - 1)]; 
        } 
        return $randomString; 
    }

    public function generate_form_id($form_prefix) {
        $this->form_postfix = $this->randomString();
        $this->form_search_details = $form_prefix."_".$this->form_postfix;
        
        return $this->form_search_details;
    }

    public function configureActions(){
        /**
         * Сбрасываем фильтры по-умолчанию
         * Необходимо возвращать prefilters и postfilters для Action-методов, 
         * иначе будет возвращаться 401 ошибка 
         */
        return [
            'GetDetails' => [
                'prefilters' => [],
                'postfilters' => []
            ]
        ];
    }

    public function executeComponent(){// подключение модулей (метод подключается автоматически)
        try{
            // Проверка подключения модулей
            $this->checkModules();
            // Создаем объект api
            $this->API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);
            // Генерируем название формы
            $this->form_search_details = $this->generate_form_id("Search_details");
            // Формируем ссылку до шаблона
            $this->arParams["templatePath"] = $_SERVER['DOCUMENT_ROOT'].$this->getPath()."/templates/".$this->arParams["TEMPLATE_NAME"]."/";
            // 
            $this->arParams["filter_type"] = array(
                "BRAND" => "бренд",
                "PRICE" => "Цена за единицу товара"
            );
            // формируем arResult
            $this->getResult($this->form_search_details);
            // подключение шаблона компонента
            $this->includeComponentTemplate();
        }
        catch (SystemException $e){
            ShowError($e->getMessage());
        }
    }

    protected function checkModules(){// если модуль не подключен выводим сообщение в catch (метод подключается внутри класса try...catch)
        if (!Loader::includeModule('iblock')){
            throw new SystemException(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
        }
    }


    public function onPrepareComponentParams($arParams){//обработка $arParams (метод подключается автоматически)
        return $arParams;
    }

    protected function getResult($form_search_details){ // подготовка массива $arResult (метод подключается внутри класса try...catch)
        // Формируем массив arResult
        $this->arResult["form_search_details"] = $this->form_search_details;
        // Передаем параметры в сессию, чтобы получить иметь доступ в ajax
        $_SESSION['arParams'] = $this->arParams;


        // Получение всех записей
        $aRow =  PriceConstructorSettingsTable::getList([
        'select' => 
        [
            "ID",
            'FILTER_GLOBAL_TYPE',
            'FILTER_ITEMS',
            'FILTER_TYPE',
            'FIRTS_FIELD',
            'MARKUP_TYPE',
            'MARKUP_VALUE',
        ],
        'filter' => [
            // '=ID'=>1
        ],
        ])->fetchAll();

        foreach ($aRow as $arkey => $arItem) {
            if( $arItem["FILTER_GLOBAL_TYPE"] == "BRAND" ){
                $aRow[$arkey]["settings"]["disable_fields"]["FILTER_TYPE"] = 'Y';
                $aRow[$arkey]["settings"]["disable_fields"]["FIRTS_FIELD"] = 'Y';
            };
            if( $arItem["FILTER_GLOBAL_TYPE"] == "PRICE" ){
                $aRow[$arkey]["settings"]["disable_fields"]["FILTER_ITEMS"] = 'Y';
            };
        };



        $this->arResult["BD_INFO"] = $aRow;
        $this->arResult["brands"] = $this->get_brands();

        return $this->arResult;
    }

    public function GetTableItemAction(){ // Поиск деталей
        $request = Application::getInstance()->getContext()->getRequest();
        
        // получаем файлы, post
        $post = $request->getPostList();
        $files = $request->getFileList()->toArray();
        
        // Получаем параметры компонента из сессии
        $this->arParams = $_SESSION['arParams'];

        // Создаем объект api
        $this->API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);

        $arResult["filter_type"] = $this->arParams["filter_type"];

        $arResult["LastItem"] = $post["LastItem"]+1;

        ob_start();
        include($this->arParams["templatePath"] . 'page_block/table_item.php');
        $output = ob_get_contents(); // Получаем верстку из подключенного файла
        ob_end_clean(); // Завершаем буферизацию и очищаем буфер

        return $output;
    }

    public function get_brands(){
        // Выводим все бренды
        foreach ($this->API->GetAllBrands()["DATA"] as $arkey => $arItem) {
            $result[] = $arItem["brand"];
        };

        return $result;
    }

    public function GetItemOptionsAction(){ // Поиск деталей
        $request = Application::getInstance()->getContext()->getRequest();
        
        // получаем файлы, post
        $post = $request->getPostList();
        $files = $request->getFileList()->toArray();
        
        // Получаем параметры компонента из сессии
        $this->arParams = $_SESSION['arParams'];

        // Создаем объект api
        $this->API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);

        if( $post["ItemCode"] == "BRAND" ){
            // Выводим все бренды
            foreach ($this->API->GetAllBrands()["DATA"] as $arkey => $arItem) {
                $result[] = $arItem["brand"];
            }
        };

        if( $post["ItemCode"] == "PRICE" ){
            // Выводим обычный input
            $result = array();
        };

        return $result;
    }

    public function DeleteSettingsItemAction(){ // Поиск деталей
        $request = Application::getInstance()->getContext()->getRequest();
        
        // получаем файлы, post
        $post = $request->getPostList();
        $files = $request->getFileList()->toArray();
        
        // Получаем параметры компонента из сессии
        $this->arParams = $_SESSION['arParams'];

        PriceConstructorSettingsTable::delete( $post["ElementID"] );
        return true;
    }


    public function SaveSettingsAction(){ // Поиск деталей
        $request = Application::getInstance()->getContext()->getRequest();
        
        // получаем файлы, post
        $post = $request->getPostList();
        $files = $request->getFileList()->toArray();
        
        // Получаем параметры компонента из сессии
        $this->arParams = $_SESSION['arParams'];

        // Создаем объект api
        $this->API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);
        
        foreach ( $post["NewSettings"] as $arkey => $arItem ) {
            
            $aRow =  PriceConstructorSettingsTable::getList([
                'select' => 
                [
                    'FILTER_GLOBAL_TYPE',
                    'FILTER_ITEMS',
                    'FILTER_TYPE',
                    'FIRTS_FIELD',
                    'MARKUP_TYPE',
                    'MARKUP_VALUE'
                ],
                'filter' => ['=ID'=>$arItem["ID"]],
                ])->fetchAll();


            $FILTER_GLOBAL_TYPE = ( $arItem["FILTER_GLOBAL_TYPE"] ) ? $arItem["FILTER_GLOBAL_TYPE"] : "" ;
            $FILTER_ITEMS = ( $arItem["FILTER_ITEMS"] ) ? $arItem["FILTER_ITEMS"] : "" ;
            $FILTER_TYPE = ( $arItem["FILTER_TYPE"] ) ? $arItem["FILTER_TYPE"] : "" ;
            $FIRTS_FIELD = ( $arItem["FIRTS_FIELD"] ) ? $arItem["FIRTS_FIELD"] : "" ;
            $MARKUP_TYPE = ( $arItem["MARKUP_TYPE"] ) ? $arItem["MARKUP_TYPE"] : "" ;
            $MARKUP_VALUE = ( $arItem["MARKUP_VALUE"] ) ? $arItem["MARKUP_VALUE"] : "" ;

            if( empty($aRow) ){
                PriceConstructorSettingsTable::add([
                    'FILTER_GLOBAL_TYPE' => $FILTER_GLOBAL_TYPE,
                    'FILTER_ITEMS' => $FILTER_ITEMS,
                    'FILTER_TYPE' => $FILTER_TYPE,
                    'FIRTS_FIELD' => $FIRTS_FIELD,
                    'MARKUP_TYPE' => $MARKUP_TYPE,
                    "MARKUP_VALUE" => $MARKUP_VALUE,
                ]);
            } else{
                PriceConstructorSettingsTable::update($arItem["ID"],[
                    'FILTER_GLOBAL_TYPE' => $FILTER_GLOBAL_TYPE,
                    'FILTER_ITEMS' => $FILTER_ITEMS,
                    'FILTER_TYPE' => $FILTER_TYPE,
                    'FIRTS_FIELD' => $FIRTS_FIELD,
                    'MARKUP_TYPE' => $MARKUP_TYPE,
                    'MARKUP_VALUE' => $MARKUP_VALUE,
                ]);
            };

        };

        return $test;
    }

}