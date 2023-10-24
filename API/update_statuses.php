<?
$DOCUMENT_ROOT = "/home/bitrix/www";
$_SERVER["DOCUMENT_ROOT"] = $DOCUMENT_ROOT;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Sale\Order;
use Bitrix\Sale;
use Bitrix\Main\Loader;

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Entity;
use Bitrix\Main\Application;

Loader::includeModule('sale');

include_once( $_SERVER["DOCUMENT_ROOT"]."/local/API/settings.php");
include_once( $_SERVER["DOCUMENT_ROOT"]."/local/API/SearchDetailAPI.php");

$API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);

/**
 * Логирование
 */
$log = ( (isset($_GET["log"]) && $_GET["log"] == "Y") || (isset($log) && $log == "Y") ) ? true : false;

// Статусы при которых могут меняться статусы заказов
$statusToCheck = array( 'AN', "AR", "AT", "AY", "AU", "AI", "AO", "AP", "AA", "AS", "AD", "TS" );

$orders = Order::getList(array(
    'select' => array('ID', 'STATUS_ID',),
    'filter' => array('=STATUS_ID' => $statusToCheck),
));

while ($order = $orders->fetch()) {
    $orderId = $order['ID'];
    $statusId = $order['STATUS_ID'];

    $order = \Bitrix\Sale\Order::load($orderId);
    $propertyCollection = $order->getPropertyCollection();
    $apiOrderProperty = $propertyCollection->getItemByOrderPropertyCode('API_ORDER_ID');
    
    if ($apiOrderProperty) {
        $apiOrderId = $apiOrderProperty->getValue();
    } else {
        $apiOrderId = null; // Свойство не найдено
    };

    /**
     * Формируем массив:
     *  ID товара битрикс
     *  ID статуса
     *  API заказа API
     */
    $result[ $apiOrderId ] = array(
        "ID" => $orderId,
        "StatusID" => $statusId,
        "ApiOrderId" => $apiOrderId,
    );

    $ApiOrderIdList[] = $apiOrderId;

};

/**
 * Получем тсатус заказов
 */
if( !empty($ApiOrderIdList) ){
    $orderList = $API->GetOrderStatus( $ApiOrderIdList )["DATA"];
    foreach ($orderList as $arkey => $arItem) {
        $product_id = $result[ $arItem["order_id"] ]["ID"]; // id заказа битрикс по id заказа api
        $APIOrderStatus = $API->StatusList[ $arItem["status_id"] ]; // code статуса битрикс по id статуса api

        if( !is_null($APIOrderStatus) ){ // Статус предусмотрен 
            $order = Sale\Order::load($product_id); // объект заказа
            $status = $order->getField("STATUS_ID"); // текущий статус заказа
            if( $status != $APIOrderStatus ){
                if( $log ) echo "Статус Заказ ".$product_id." сменился c ".$status." на ".$APIOrderStatus."<br>";
                $order->setField("STATUS_ID", $APIOrderStatus);
                $order->save();
            }else{
                if( $log ) echo "Статус Заказ ".$product_id." не сменился. Сейчас установлен ".$status."<br>";
            };
        };
    };
}else{
    if( $log ) echo "Товаров ожидающих смену статуса не найдено.";
};?>