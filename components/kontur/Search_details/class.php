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

use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;
use Bitrix\Main\Context;
use Bitrix\Sale\PropertyValueCollection;

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
            // Генерируем название формы
            $this->form_search_details = $this->generate_form_id("Search_details");
            // Формируем ссылку до шаблона
            $this->arParams["templatePath"] = $_SERVER['DOCUMENT_ROOT'].$this->getPath()."/templates/".$this->arParams["TEMPLATE_NAME"]."/";
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

        // Создаем объект api
        $this->API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);

        // Выводим все способы доставки
        $this->arResult["Deliveries"] = $this->API->GetAllDeliveries()["DATA"];

        // Выводим все бренды
        foreach ($this->API->GetAllBrands()["DATA"] as $arkey => $arItem) {
            $this->arResult["Brands"][] = $arItem["brand"];
        }
  
        $this->arResult["DELIVERY_KEY"] = (defined('DELIVERY_KEY')) ? $this->API->DELIVERY_KEY : "";

        return $this->arResult;
    }

    public function GetPriceRecalculationRule(){
        // Получение всех записей
        $rules =  PriceConstructorSettingsTable::getList([
            'select' => ["*"],
            'filter' => [],
            ])->fetchAll();

        return $rules;
    }

    public function CalculatePrice( $price, $arithmetic_sign, $value ){
        if( $price == "" || $arithmetic_sign == "" || $value == "" ){
            return false;
        };

        switch ($arithmetic_sign) {
            case '%':
                $result = $price * (1 + ($value / 100));
                break;
            case '+':
                $result = $price + $value;
                break;
        };

        return $result;
    }

    public function PriceRecalculation( $ItemsArray ){
        // Получение массива с параметрами
        $result = $ItemsArray;
        $filters = $this->GetPriceRecalculationRule(); 

        foreach ($ItemsArray as $typekey => $typeItem) { // ['Результат', 'похожие товары']
            foreach ($typeItem as $productkey => $productItem) { // товары

                $calculated_prices = []; // массив для хранения цен
                foreach ($filters as $filterkey => $filterItem) { // Правила
                    
                    switch ($filterItem["FILTER_GLOBAL_TYPE"]) {
                        case 'BRAND':
                            // Если бренд из правил совпадает с брендом товара, делаем наценку
                            if( $filterItem["FILTER_ITEMS"] == $productItem["brand"] ){
                                $calc = $this->CalculatePrice(
                                    $productItem["price"],
                                    $filterItem["MARKUP_TYPE"],
                                    $filterItem["MARKUP_VALUE"]
                                );
                                if( $calc !== false ){$calculated_prices[] = $calc;};
                            };
                            break;

                        case 'PRICE':
                            // Проверка по знаку
                            switch ($filterItem["FILTER_TYPE"]){
                                case '>':
                                    if( $productItem["price"] > $filterItem["FIRTS_FIELD"] ){
                                        $calc = $this->CalculatePrice(
                                            $productItem["price"],
                                            $filterItem["MARKUP_TYPE"],
                                            $filterItem["MARKUP_VALUE"]
                                        );
                                        if( $calc !== false ){$calculated_prices[] = $calc;};
                                    };
                                    break;
                                case '>=':
                                    if( $productItem["price"] >= $filterItem["FIRTS_FIELD"] ){
                                        $calc = $this->CalculatePrice(
                                            $productItem["price"],
                                            $filterItem["MARKUP_TYPE"],
                                            $filterItem["MARKUP_VALUE"]
                                        );
                                        if( $calc !== false ){$calculated_prices[] = $calc;};
                                    };
                                    break;
                                case '<':
                                    if( $productItem["price"] < $filterItem["FIRTS_FIELD"] ){
                                        $calc = $this->CalculatePrice(
                                            $productItem["price"],
                                            $filterItem["MARKUP_TYPE"],
                                            $filterItem["MARKUP_VALUE"]
                                        );
                                        if( $calc !== false ){$calculated_prices[] = $calc;};
                                    };
                                    break; 
                                case '<=':
                                    if( $productItem["price"] <= $filterItem["FIRTS_FIELD"] ){
                                        $calc = $this->CalculatePrice(
                                            $productItem["price"],
                                            $filterItem["MARKUP_TYPE"],
                                            $filterItem["MARKUP_VALUE"]
                                        );
                                        if( $calc !== false ){$calculated_prices[] = $calc;};
                                    };
                                    break;                                     
                                case '=':
                                    if( $productItem["price"] == $filterItem["FIRTS_FIELD"] ){
                                        $calc = $this->CalculatePrice(
                                            $productItem["price"],
                                            $filterItem["MARKUP_TYPE"],
                                            $filterItem["MARKUP_VALUE"]
                                        );
                                        if( $calc !== false ){$calculated_prices[] = $calc;};
                                    };
                                    break;
                            };
                            break;
                    };
                };
                $result[$typekey][$productkey]["CalculatedPrice"] = max($calculated_prices);
            };
        };

        return $result;
    }

    public function GetDetailsAction(){ // Поиск деталей
        $request = Application::getInstance()->getContext()->getRequest();
        
        // получаем файлы, post
        $post = $request->getPostList();
        $files = $request->getFileList()->toArray();
        
        // Получаем параметры компонента из сессии
        $this->arParams = $_SESSION['arParams'];
        
        // Создаем объект api
        $this->API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);

        // Вычисления
        if( isset($post["brand"])){
            // Получить деталь с указанием бренда
            $arResult = $this->API->GetDetails($post["detail_code"], $post["deliveries"], $post["brand"]);
        }else{
            // Получить деталь без указания бренда
            $arResult = $this->API->GetDetails($post["detail_code"], $post["deliveries"]);
        };

        $arResult = $this->PriceRecalculation( $arResult );
        ob_start();
        include($this->arParams["templatePath"] . 'page_block/product_table.php');
        $output = ob_get_contents(); // Получаем верстку из подключенного файла
        ob_end_clean(); // Завершаем буферизацию и очищаем буфер

        return $output;
    }


    /**
     * Ищет товар со свойством OFFER_KEY == offerKey
     * Если оно есть, устанавливает его кол-во равным $quantity
     * Возвращает true, если товар найден
     * Возвращает false, если товар не найден
     */
    function quantityUp( $offerKey, $quantity ){
        // Корзина пользователя, id сайта
        $fUserID = IntVal(CSaleBasket::GetBasketUserID(True));
        $siteId = Bitrix\Main\Context::getCurrent()->getSite();

        // Добавляем кастомный товар в корзину
        $basket = \Bitrix\Sale\Basket::loadItemsForFUser($fUserID, $siteId);
        $basketItems = $basket->getBasketItems();

        foreach ($basketItems as $basketItem) {

            $propertyCollection = $basketItem->getPropertyCollection();
            foreach ($propertyCollection as $property) {
                $propertyCODE = $property->getField('CODE');
                $propertyValue = $property->getField('VALUE');

                if( $propertyCODE == "OFFER_KEY" && $propertyValue == $offerKey ){
                    $newQuantity = $quantity;
                    $basketItem->setField('QUANTITY', $newQuantity);
                    $basket->save();
                    
                    return true;
                };
            };
        };

        return false;
    }

    /**
     * Добавление в корзину
     */
    public function ADD2BasketAction(){
        $request = Application::getInstance()->getContext()->getRequest();
        
        // получаем файлы, post
        $post = $request->getPostList();
        $files = $request->getFileList()->toArray();
        
        // Получаем параметры компонента из сессии
        $this->arParams = $_SESSION['arParams'];
        
        // Создаем объект api
        $this->API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);
        
        $productInfo = $this->API->GetProductInfoBy_OfferKey( $post["offer_key"], $post["code"], $post["deliveries"], $post["brand"] );
        
        $isProductInBasket = $this->quantityUp( $post["offer_key"], $post["quentity"] );

        if( $isProductInBasket ) return false;

        // Получаем пересчитанную цену
        $newPrice = $this->PriceRecalculation( array(array($productInfo)) )[0][0]["CalculatedPrice"];

        // Добавление товара в корзину
        if( $productInfo ){ // товар найден

            // Корзина пользователя, id сайта
            $fUserID = IntVal(CSaleBasket::GetBasketUserID(True));
            $siteId = Bitrix\Main\Context::getCurrent()->getSite();
            
            // Добавляем кастомный товар в корзину
            $basket = \Bitrix\Sale\Basket::loadItemsForFUser($fUserID, $siteId);


            $item = $basket->createItem('catalog', "APICustom_".$productInfo["offer_key"]);
            $item->setFields([
                'QUANTITY' => $post["quentity"],
                'CURRENCY' => Bitrix\Currency\CurrencyManager::getBaseCurrency(),
                'LID' => Bitrix\Main\Context::getCurrent()->getSite(),
                'NAME' => $productInfo["name"],
                'PRICE' => (isset($newPrice) && $newPrice!="" ) ? $newPrice : $productInfo["price"],
                'CUSTOM_PRICE' => 'Y',
            ]);
            $basket->save();
                        
            // Задаем свойства товару
            $basketPropertyCollection = $item->getPropertyCollection(); 
            $basketPropertyCollection->getPropertyValues();
            $basketPropertyCollection->setProperty([
                [
                'NAME' => 'Заказ с auto euro',
                'CODE' => 'AUTO_EURO_PRODUCT',
                'VALUE' => 'Y',
                'SORT' => 100,
                ],
                [
                    'NAME' => 'ID способа доставки',
                    'CODE' => 'DELIVERIES',
                    'VALUE' => $post["deliveries"],
                    'SORT' => 100,
                ],
                [
                    'NAME' => 'ID товара API',
                    'CODE' => 'OFFER_KEY',
                    'VALUE' => $post["offer_key"],
                    'SORT' => 100,
                ],
                [
                    'NAME' => 'Артикул товара API',
                    'CODE' => 'AUTO_EURO_ARTICLE',
                    'VALUE' => $post["code"],
                    'SORT' => 100,
                ],
            ]);
            // Сохраняем свойства товара
            $basketPropertyCollection->save();

        };

        return false;
    }

    /**
     * Проверяет корзину на конфликтующие детали
     * Если в качестве SearchingTypeOfProduct передать default:
     *      Отдаст false, если обычных товаров нет
     *      Отдаст массив id элементов корзины если такие товары есть
     * 
     * Если в качестве SearchingTypeOfProduct передать auto_euro:
     *      Отдаст false, если обычных товаров нет
     *      Отдаст массив id элементов корзины если такие товары есть
     */
    public function CheckBasketForConflictingProductsAction(){
        $request = Application::getInstance()->getContext()->getRequest();
        
        // получаем файлы, post
        $post = $request->getPostList();
        $files = $request->getFileList()->toArray();

        if( !isset($post["SearchingTypeOfProduct"]) ){
            return array("error"=>"no SearchingTypeOfProduct");
        };

        // Получаем параметры компонента из сессии
        $this->arParams = $_SESSION['arParams'];
        
        // Создаем объект api
        $this->API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);

        // Корзина пользователя, id сайта
        $fUserID = IntVal(CSaleBasket::GetBasketUserID(True));
        $siteId = Bitrix\Main\Context::getCurrent()->getSite();

        // Загружаем корзину пользователя
        $basket = Basket::loadItemsForFUser($fUserID, $siteId);

        // Получаем все товары в корзине
        $basketItems = $basket->getBasketItems();

        foreach ($basketItems as $basketItem) {
            $ItemId = $basketItem->getId();
            $productId = $basketItem->getProductId();
            $productName = $basketItem->getField('NAME');
            $quantity = $basketItem->getQuantity();
            $price = $basketItem->getPrice();
            
            $properties = $basketItem->getPropertyCollection();
            $propertyValues = $properties->getPropertyValues();

            $BasketElement = array(
                "ItemId" => $ItemId,
                "ProductId" => $productId,
                "ProductName" => $productName,
                "ProductQuantity" => $quantity,
                "ProductPrice" => $price,
            );

            foreach ($propertyValues as $prop_key => $propertyValue) {
    
                $BasketElement["PROPS"][$prop_key] = $propertyValue;
            };

            $BasketItemList[] = $BasketElement;

        };

        $AutoEuroProducts = array();
        $DefaultProducts = array();
        foreach ($BasketItemList as $arKey => $arItem) {
            if( isset($arItem["PROPS"]["AUTO_EURO_PRODUCT"]) && $arItem["PROPS"]["AUTO_EURO_PRODUCT"]["VALUE"] == "Y" ){
                $AutoEuroProducts[] = $arItem["ItemId"];
            }else{
                $DefaultProducts[] = $arItem["ItemId"];
            };
        };

        if ( $post["SearchingTypeOfProduct"] == 'default' ){
            $result = ( empty($DefaultProducts) ) ? true : $DefaultProducts;
        };

        if ( $post["SearchingTypeOfProduct"] == 'auto_euro' ){
            $result = ( empty($AutoEuroProducts) ) ? true : $AutoEuroProducts;
        };

        return $result;
    }


    public function ClearBasketAction(){
        $request = Application::getInstance()->getContext()->getRequest();
        
        // получаем файлы, post
        $post = $request->getPostList();
        $files = $request->getFileList()->toArray();

        if( !isset($post["ItemID"]) ){
            return array("error"=>"no ItemID");
        };

        // Корзина пользователя, id сайта
        $fUserID = IntVal(CSaleBasket::GetBasketUserID(True));
        $siteId = Bitrix\Main\Context::getCurrent()->getSite();

        // Загружаем корзину пользователя
        $basket = Basket::loadItemsForFUser($fUserID, $siteId);

        foreach ($post["ItemID"] as $arkey => $arItem) {
            $basket->getItemById($arItem)->delete();
            $basket->save();
        };

        return true;
    }


}