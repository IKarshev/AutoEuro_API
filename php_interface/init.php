<?
// Инициализация класса работы с API
include_once( $_SERVER["DOCUMENT_ROOT"]."/local/API/settings.php");
include_once( $_SERVER["DOCUMENT_ROOT"]."/local/API/SearchDetailAPI.php");

$API = new SearchDetailAPI(API_TOKEN, API_URL, API_VERSION, PAYER_KEY, DELIVERY_KEY);

// Добавляем вкладки в админку
include(__DIR__ . "/admin_pages.php");

// Обработчик события OnSaleOrderSaved 
include(__DIR__ . "/OnSaleOrderSaved.php");
?>