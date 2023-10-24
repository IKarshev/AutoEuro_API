<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");?>
<?
use Bitrix\Main\Page\Asset;
Asset::getInstance()->addCss(SITE_TEMPLATE_PATH . "/style/css/select2.min.css");
Asset::getInstance()->addJs(SITE_TEMPLATE_PATH . "/js/select2.min.js");
?>
<script>
	var form_search_details = <?=CUtil::PhpToJSObject($arResult["form_search_details"])?>;
</script>

<!-- <pre><?// print_r($arResult);?></pre> -->

<a href="" class="add_rule"> <b>+</b> Добавить правило</a>

<div class="rule_container">
    <table>
        <thead>
            <tr>
                <td class="width50">ID</td>
                <td>Тип фильтрации</td>
                <td class="width360">Выборка свойства</td>
                <td>Оператор</td>
                <td>Значение</td>
                <td>Тип наценки</td>
                <td>Значение наценки</td>
                <td></td>
            </tr>
        </thead>
        <tbody class="item_cont">

            <?foreach ($arResult["BD_INFO"] as $arkey => $arItem):?>
                <tr class="rule_item">
                    <td class="ID width50"><?=$arItem["ID"]?></td>
                    <td>
                        <select class="js-select2 filter_global_type" name="" id="">
                            <?foreach ($arParams["filter_type"] as $filter_typekey => $filter_typeItem):?>
                                <option value="<?=$filter_typekey?>" <?=($arItem["FILTER_GLOBAL_TYPE"] == $filter_typekey) ? "selected" : ""?> ><?=$filter_typeItem?></option>
                            <?endforeach;?>
                        </select>
                    </td>
                    <td class="width360">
                        <select class="js-select2 filter_items" name="" id="" <?=($arItem["settings"]["disable_fields"]["FILTER_ITEMS"]) ? "disabled" : ""?>>
                            
                            <?if($arItem["FILTER_GLOBAL_TYPE"] == "BRAND"):?>
                                <?foreach ($arResult["brands"] as $brandkey => $brandItem):?>
                                    <option value="<?=$brandItem?>" <?=($arItem["FILTER_ITEMS"] == $brandItem) ? "selected" : ""?> ><?=$brandItem?></option>
                                <?endforeach;?>
                            <?endif;?>

                        </select>
                    </td>
                    <td>
                        <select class="js-select2 filter_type" name="" id="" <?=($arItem["settings"]["disable_fields"]["FILTER_TYPE"]) ? "disabled" : ""?>>
                            <option value="=" <?=($arItem["FILTER_TYPE"] == "=") ? "selected" : ""?> >=</option>
                            <option value=">" <?=($arItem["FILTER_TYPE"] == ">") ? "selected" : ""?> >></option>
                            <option value=">=" <?=($arItem["FILTER_TYPE"] == ">=") ? "selected" : ""?> >>=</option>
                            <option value="<" <?=($arItem["FILTER_TYPE"] == "<") ? "selected" : ""?> ><</option>
                            <option value="<=" <?=($arItem["FILTER_TYPE"] == "<=") ? "selected" : ""?> ><=</option>
                        </select>
                    </td>
                    <td>
                        <input class="firts_field" value="<?=$arItem["FIRTS_FIELD"]?>" type="text" placeholder="Введите" <?=($arItem["settings"]["disable_fields"]["FIRTS_FIELD"]) ? "disabled" : ""?>>
                    </td>
                    <td>
                        <select class="js-select2 markup_type" name="" id="">
                            <option value="%" <?=($arItem["MARKUP_TYPE"] == "%") ? "selected" : ""?>>%</option>
                            <option value="+" <?=($arItem["MARKUP_TYPE"] == "+") ? "selected" : ""?>>+</option>
                        </select>
                    </td>
                    <td>
                        <input class="markup_value" type="text" placeholder="Введите значение наценки" value="<?=$arItem["MARKUP_VALUE"]?>">
                    </td>
                    <td>
                        <a href="" data-item_id="<?=$arItem["ID"]?>" class="delete_item">
                            <svg width="18" height="18" viewBox="0 0 18 18" fill="inherit" xmlns="http://www.w3.org/2000/svg">
                                <path d="M10.0625 8.65026L9.70813 9.00409L10.0625 9.35793L16.3625 15.6479L16.3637 15.6491C16.4106 15.6956 16.4478 15.7509 16.4731 15.8118C16.4985 15.8727 16.5116 15.9381 16.5116 16.0041C16.5116 16.0701 16.4985 16.1355 16.4731 16.1964C16.4478 16.2573 16.4106 16.3126 16.3637 16.3591L16.3608 16.362C16.3143 16.4089 16.259 16.4461 16.1981 16.4714C16.1372 16.4968 16.0718 16.5099 16.0058 16.5099C15.9398 16.5099 15.8744 16.4968 15.8135 16.4714C15.7526 16.4461 15.6973 16.4089 15.6508 16.362L15.6496 16.3608L9.35963 10.0608L9.0058 9.70642L8.65196 10.0608L2.36196 16.3608L2.3608 16.362C2.31431 16.4089 2.25901 16.4461 2.19809 16.4714C2.13716 16.4968 2.07181 16.5099 2.0058 16.5099C1.93979 16.5099 1.87444 16.4968 1.81351 16.4714C1.75258 16.4461 1.69728 16.4089 1.6508 16.362L1.6479 16.3591C1.60103 16.3126 1.56384 16.2573 1.53845 16.1964C1.51307 16.1354 1.5 16.0701 1.5 16.0041C1.5 15.9381 1.51307 15.8727 1.53845 15.8118C1.56384 15.7509 1.60103 15.6956 1.6479 15.6491L1.64907 15.6479L7.94907 9.35793L8.30347 9.00409L7.94907 8.65026L1.64935 2.36054C1.64932 2.3605 1.64928 2.36047 1.64924 2.36043C1.55478 2.2659 1.50171 2.13773 1.50171 2.00409C1.50171 1.8704 1.55482 1.74218 1.64935 1.64765C1.74389 1.55311 1.8721 1.5 2.0058 1.5C2.13944 1.5 2.26761 1.55307 2.36214 1.64754C2.36217 1.64757 2.36221 1.64761 2.36224 1.64765L8.65196 7.94736L9.0058 8.30176L9.35963 7.94736L15.6494 1.64765C15.7439 1.55311 15.8721 1.5 16.0058 1.5C16.1395 1.5 16.2677 1.55311 16.3622 1.64765C16.4568 1.74218 16.5099 1.8704 16.5099 2.00409C16.5099 2.13778 16.4568 2.266 16.3622 2.36054L10.0625 8.65026Z" stroke="inherit"/>
                            </svg>
                        </a>
                    </td>
                </tr>    
            <?endforeach;?>

        </tbody>
    </table>
</div>

<div class="button_container">
    <input class="submit_btn" type="submit" name="apply" value="Применить" title="Сохранить и остаться в форме">
    <div style="display:none;" class="status"></div>
</div>