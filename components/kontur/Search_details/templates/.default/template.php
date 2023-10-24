<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");?>
<?
use Bitrix\Main\Page\Asset;
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/style/css/select2.min.css");
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/select2.min.js");
?>
<script>
	var form_search_details = <?=CUtil::PhpToJSObject($arResult["form_search_details"])?>;
</script>


<!-- <pre><?//print_r($arResult);?></pre> -->

<div class="search_details">
    <form id="<?=$arResult["form_search_details"]?>" class="search_form" method="post" action="" enctype="multipart/form-data">

        <?// Способ доставки?>
        <div class="input_cont frow">    
            <select class="js-select2" name="deliveries" id="deliveries" <?=(isset($arResult['DELIVERY_KEY']) && $arResult['DELIVERY_KEY']!="") ? "disabled" : ""?>>
                <option value="" disabled >Выберите способ доставки</option>

                <?foreach ($arResult["Deliveries"] as $arkey => $arItem):?>
                    <option data-time_shift_msk="<?=$arItem["time_shift_msk"]?>" value="<?=$arItem["delivery_key"]?>" <?=($arItem["delivery_key"] == $arResult["DELIVERY_KEY"]) ? "selected" : "" ;?> ><?=$arItem["delivery_name"]?></option>
                <?endforeach;?>
            </select>
        </div>

        <?// Артикул?>
        <div class="input_cont frow">
            <input id="detail_code" name="detail_code" class="search" type="text" placeholder="Введите артикул товара">
        </div>

        <?// Бренд?>
        <div class="input_cont frow">    
            <select class="js-select2" name="brand" id="brand">
                <option value="" disabled selected>Выберите Бренд</option>

                <?foreach ($arResult["Brands"] as $arkey => $arItem):?>
                    <option value="<?=$arItem?>"><?=$arItem?></option>
                <?endforeach;?>
            </select>
        </div>

        <?// errors?>
        <div class="input_cont frow">
            <div class="error_placement"></div>
        </div>

        <button type="submit">Поиск</button>

    </form>

    <div class="tab_container">
        <div class="tab_panel">
            <a class="toggle_tab active" data-tabname="fits" href="#">Результат поиска</a>
            <a class="toggle_tab" data-tabname="similar" href="#">Похожие товары</a>
        </div>
        <div id="product_table" class="product_table tabs"></div>
    </div>
</div>

