<?
class SearchDetailAPI
{
    public $APItoken;
    public $URL;
    public $VERSION;
    public $PAYER_KEY;
    public $DELIVERY_KEY;

    public function __construct($APItoken, $URL, $VERSION, $PAYER_KEY, $DELIVERY_KEY){
        $this->APItoken = $APItoken;
        $this->URL = $URL;
        $this->VERSION = $VERSION;
        $this->PAYER_KEY = $PAYER_KEY;
        $this->DELIVERY_KEY = $DELIVERY_KEY;

        $this->StatusList = array(
            // AE - Ошибка в формировании заказа
            // AN - Заказ успешно сформирован
            "110" => "TS", // Тестовый статус

            "10" => "AR", // Товар зарезервирован и готов к отгрузке
            "11" => "AT", // Товар зарезервирован и ожидает в Пункте Выдачи Заказов
            "12" => "AY", // Данный товар на складе, но будет отгружен как только весь остальной товар из заказа поступит на склад (статус «резерв»)
            "50" => "AU", // Заказ принят
            "51" => "AI", // Заказ отправлен поставщику
            "52" => "AO", // Поставщик подтвердил поставку товара
            "70" => "AP", // Товар поступил на склад и в ближайшее время будет готов к отгрузке
            "99" => "AA", // Отправлен запрос на отмену поставки товара
            "160" => "AS", // Товар поступил в ПВЗ и готов к выдаче
            "180" => "AD", // Товар передан водителю и будет доставлен в ближайшее время
        );

    }

    public function curlPost( string $Method, array $data = array() ){
        // generate query url
        $url = $this->URL."/v".$this->VERSION."/json/".$Method."/".$this->APItoken;

        // curl
        $ch = curl_init(); // Инициализация cURL-сессии

        // Установка параметров запроса
        curl_setopt($ch, CURLOPT_URL, $url); // Устанавливаем URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Возвращать ответ в виде строки
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json', // Устанавливаем заголовок Accept - json
        ]);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Отключаем проверку SSL-сертификата (не рекомендуется в продакшене)

        // Устанавливаем метод запроса на POST
        curl_setopt($ch, CURLOPT_POST, 1);

        // Устанавливаем данные, которые будут отправлены
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

        // Выполнение запроса и сохранение ответа
        $response = curl_exec($ch);

        // Проверка на ошибки
        if (curl_errno($ch)) {
            return 'Ошибка cURL: ' . curl_error($ch);
        }
        // Завершение cURL-сессии
        curl_close($ch);
        return json_decode($response, true);
    }
    public function curlGet( string $Method, array $data = array() ){
        // generate query url
        $url = $this->URL."/v".$this->VERSION."/json/".$Method."/".$this->APItoken;
        
        if( !empty($data) ){
            foreach ($data as $arkey => $arItem) {
                $query .= "$arkey=$arItem&";
            }
            $url .= "?".substr($query, 0, -1);
        };

        // curl
        $ch = curl_init(); // Инициализация cURL-сессии

        // Установка параметров запроса
        curl_setopt($ch, CURLOPT_URL, $url); // Устанавливаем URL
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Возвращать ответ в виде строки
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json', // Устанавливаем заголовок Accept - json
        ]);

        // Выполнение запроса и сохранение ответа
        $response = curl_exec($ch);

        // Проверка на ошибки
        if (curl_errno($ch)) {
            return 'Ошибка cURL: ' . curl_error($ch);
        }
        // Завершение cURL-сессии
        curl_close($ch);
        return json_decode($response, true);
    }

    public function GetAllBrands(){
        $result = $this->curlGet("get_brands");
        return $result;
    }

    public function GetAllDeliveries(){
        $result = $this->curlGet("get_deliveries");
        return $result;
    }

    /**
     * Подготовка массива $result, где 
     * $result["fits"] — это элементы с идентичным артикулом
     * $result["similar"] — это элементы с похожим артикулом
     */
    public function GetDetails($code, $delivery_key, $brand = ""){        
        $fits = array();
        $similar = array();

        if( $brand == "" ){
            // Поиск всевозможных брендов по коду
            $brands = $this->curlPost("search_brands", array(
                "code" => $code,
            ))["DATA"];

            foreach ($brands as $arkey => $arItem) {
                // Поиск товаров
                $BrandList[ $arItem["brand"] ] = $this->curlPost("search_items", array(
                    "brand" => $arItem["brand"],
                    "code" => $code,
                    "delivery_key" => $delivery_key,
                ))["DATA"];

                foreach ($BrandList as $BrandListKey => $BrandListItem) {
                    foreach ($BrandListItem as $BrandKey => $BrandItem) {
                        if( $BrandItem["code"] == $code ){
                            $fits[] = $BrandItem;
                        }else{
                            $similar[] = $BrandItem;
                        };  
                    }
                };
            };
            

        }else{
            $BrandList = $this->curlPost("search_items", array(
                "brand" => $brand,
                "code" => $code,
                "delivery_key" => $delivery_key,
            ))["DATA"];

            foreach ($BrandList as $BrandKey => $BrandItem) {

                if( $BrandItem["code"] == $code ){
                    $fits[] = $BrandItem;
                }else{
                    $similar[] = $BrandItem;
                };  
            }
        };

        $result = array(
            "fits" => $fits,
            "similar" => $similar,
        );

        return $result;
    }

    public function GetProductInfoBy_OfferKey( $offerkey ,$code, $delivery_key, $brand = "" ){
        $product_list = $this->GetDetails( $code, $delivery_key, $brand );

        foreach ($product_list["fits"] as $arkey => $arItem) {
            if( $arItem["offer_key"] == $offerkey ){
                return $arItem;
            }
        }

        foreach ($product_list["similar"] as $arkey => $arItem) {
            if( $arItem["offer_key"] == $offerkey ){
                return $arItem;
            }
        }
        
        return false;
    }

    /**
     * Получить все возможные ключи доставок
     */
    public function GetAllPayerKeys(){
        $payers = $this->curlPost('get_payers')["DATA"];
        return $payers;
    }

    /**
     * delivery_key - @string ключ доставки
     * payer_key - @string ключ оплаты
     * 
     * stock_items - @array товары
     *   offer_key - @string id товара
     *   quantity - @string кол-во
     *   price - @string Максимальная цена.
     *     Если параметр указан, то делается сверка с актуальной ценой предложения и в случае превышения последней заказ не будет оформлен.
     *     Если 0, то проверка производиться не будет.
     * 
     *   comment - @string комментарий
     * 
     * wait_all_goods - string доставлять все товары сразу или по мере поступления
     *   0 - @string по мере поступления
     *   1 - @string всё сразу
     * 
     * comment - @string комментарий
     */
    public function CreateOrder( $stock_items, $wait_all_goods = "1", $comment = "" ){
        $data = array(
            'delivery_key' => $this->DELIVERY_KEY,
            'payer_key' => $this->PAYER_KEY,
            'stock_items' => json_encode($stock_items),
            'wait_all_goods' => $wait_all_goods,
            'comment' => $comment,
        );
        $order = $this->curlPost('create_order', $data);
                
        
        ob_start();
        print_r($order);
        $debug = ob_get_contents();
        ob_end_clean();
        $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/lk-params.log', 'w+');
        fwrite($fp, $debug);
        fclose($fp); 
        

        return $order;
    }

    public function GetOrderStatus( array $OrderID ){
        $result = $this->curlPost('get_orders', array(
            "orders" => json_encode( $OrderID ),
        ));

        return $result;
    }

}