<?
use Bitrix\Main\Loader;
use \Bitrix\Main\Application;
use Bitrix\Sale;
use Bitrix\Sale\Basket;
use Bitrix\Sale\Fuser;
use Bitrix\Main\Context;
use Bitrix\Sale\Order;

use Bitrix\Main\ModuleManager;
use Bitrix\Main\Entity;

Loader::includeModule("sale");



\Bitrix\Main\EventManager::getInstance()->addEventHandler( 
	'sale', 
	'OnSaleOrderSaved', 
	'MyClass::OnSaleOrderSaved'
); 
class MyClass
{
	function OnSaleOrderSaved(\Bitrix\Main\Event $event)
	{
        include_once( $_SERVER["DOCUMENT_ROOT"]."/local/API/settings.php");
        include_once( $_SERVER["DOCUMENT_ROOT"]."/local/API/SearchDetailAPI.php");
        

        $API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);

		if(!$event->getParameter("IS_NEW"))
			return;
		$order = $event->getParameter("ENTITY");
        $orderId = $order->getId(); // id текущего заказа битрикс

        $basket = $order->getBasket();

        // Получаем корзину заказа
        $basketItems = $basket->getBasketItems();

        /**
         * Получаем все товары со свойствами в корзине
         */
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

        /**
         * Отбираем товары полученные по API и другие товары
         */
        $AutoEuroProducts = array();
        $DefaultProducts = array();
        foreach ($BasketItemList as $arKey => $arItem) {

            if( isset($arItem["PROPS"]["AUTO_EURO_PRODUCT"]) && $arItem["PROPS"]["AUTO_EURO_PRODUCT"]["VALUE"] == "Y" ){
                // Товары по API
                $AutoEuroProducts[] = $arItem;
            }else{
                // Товары никак не связанные с API
                $DefaultProducts[] = $arItem;
            };
        };

        /**
         * Маловероятный сюжет:
         * Возвращаем ошибку, если в корзине есть, как товары с API, так и другие товары 
         */
        if( !empty($AutoEuroProducts) && !empty($DefaultProducts) ){
            
            // Выставляем ошибку при  "Ошибка API"
            $order->setField('STATUS_ID', 'AE');
            $order->save();
            
            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::ERROR,
                \Bitrix\Sale\ResultError::create(new \Bitrix\Main\Error("Нельзя перевести заказ в финальный статус т.к в корзине обнаружены конфликтующие товары", "ERROR_CONFLICT_PRODUCTS"))
                );
        };

        /**
         * Exit если товаров по api нет
         */
        if( empty($AutoEuroProducts) ){return true;};


        // Выдаем ошибку, если товары с API имеют разные id способов доставки
        $DeliveryCode = [];
        foreach ($AutoEuroProducts as $arkey => $arItem) {
            if( !in_array($arItem["PROPS"]["DELIVERIES"]["VALUE"], $DeliveryCode) ){
                $DeliveryCode[] = $arItem["PROPS"]["DELIVERIES"]["VALUE"];
            };
        };
        if( count($DeliveryCode)>1 ){
            
            // Выставляем ошибку при  "Ошибка API"
            $order->setField('STATUS_ID', 'AE');
            $order->save();
            return new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::ERROR,
                \Bitrix\Sale\ResultError::create(new \Bitrix\Main\Error("Нельзя перевести заказ в финальный статус т.к в корзине обнаружены товары с разными типами доставки", "ERROR_CONFLICT_DELIVERIES"))
                );
        }else{
            $DeliveryCode = $DeliveryCode[0];
        };


        /**
         * Если есть товары по api, но нет обычных, то создаем заказ по API
         */
        if( !empty($AutoEuroProducts) && empty($DefaultProducts) ){
            foreach ($AutoEuroProducts as $arkey => $arItem) {
                $OrderItems[] = array(
                    "offer_key" => $arItem["PROPS"]["OFFER_KEY"]["VALUE"],
                    "quantity" => $arItem["ProductQuantity"],
                    "price" => 0,
                    "comment" => $arItem["ProductName"]." ".$arItem["ProductQuantity"],
                );
            };

            $APIorder = $API->CreateOrder($OrderItems);
            $APIOrderID = $APIorder["DATA"][0]["order_id"]; // ID Заказа API 

            if( isset($APIorder["ERROR"]) || ( $APIorder["DATA"][0]["result"] === false || is_null($APIOrderID) ) ){ // TODO выставить статус заказа "ошибка"
                // Выставляем ошибку при  "Ошибка API"
                $order->setField('STATUS_ID', 'AE');
                $order->save();
                
                return false;
            };

            $propertyCollection = $order->getPropertyCollection();
            $allParams = $propertyCollection->getArray();
            // Получаем свойство ID заказа API
            $somePropValue = $propertyCollection->getItemByOrderPropertyId(20);
            $somePropValue->setValue( $APIOrderID );

            $order->setField('STATUS_ID', 'AN');
            // Сохраняем заказ
            $order->save();
            
        };

        return true;
	}	
}
?>