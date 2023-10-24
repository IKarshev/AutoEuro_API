<?/*
<div class="tab active" data-tabname="fits">
    <div class="product_container">
        <table>

            <thead>
                <tr>
                    <td>Артикул</td>
                    <td>Даты</td>
                    <td>Бренд</td>
                    <td>Название</td>
                    <td>Склад</td>
                    <td>В упаковке</td>
                    <td>Доступное кол-во</td>
                    <td>Цена</td>
                    <td class="add"></td>
                </tr>
            </thead>

            <tbody>
                <?foreach ($arResult["fits"] as $arkey => $arItem):?>
                    <tr class="detail" data-deliveries="<?=$post["deliveries"]?>" data-code="<?=$arItem["code"];?>" data-brand="<?=$arItem["brand"];?>" data-offer_key="<?=$arItem["offer_key"];?>" data-warehouse_key="<?=$arItem["warehouse_key"];?>">
                        <td class="detail">
                            <span class="name"><?=$arItem["code"];?></span><br>
                            <span class="info"><?=($arItem["dealer"])? "Официальный диллер" : "" ;?></span>
                        </td>
                        <td>
                            <span class="name">Крайнее время заказа</span><br>
                            <span class="info"><?=$arItem["order_before"]?></span><br>
                            <span class="name">Расчетное время доставки.</span><br>
                            <span class="info"><?=$arItem["delivery_time"]?></span><br>
                            <span class="name">Максимальное время доставки.</span><br>
                            <span class="info"><?=$arItem["delivery_time_max"]?></span>
                        </td>
                        <td><?=$arItem["brand"];?></td>
                        <td><?=$arItem["name"];?></td>
                        <td><?=($arItem["warehouse_name"]) ? $arItem["warehouse_name"] : "Заказной";?></td>
                        <td><?=$arItem["packing"];?></td>
                        <td><?=$arItem["amount"];?></td>
                        <td><?=($arItem["CalculatedPrice"]!="") ? $arItem["CalculatedPrice"] : $arItem["price"] ;?> <?=$arItem["currency"]?></td>

                        <td class="AddBasketBlock">
                            <div class="number" data-step="1" data-min="1" data-max="<?=$arItem["amount"]?>">
                                <input class="number-text" type="text" name="count" value="1">
                                <a href="#" class="number-minus">−</a>
                                <a href="#" class="number-plus">+</a>
                            </div>
                        
                            <a class="btn add2basket">В корзину</a>
                        </td>
                        
                    </tr>
                <?endforeach;?>
            </tbody>

        </table>
    </div>
</div>
*/?>

<?
$tables = array(
    "fits" => $arResult["fits"],
    "similar" => $arResult["similar"],
);

foreach ($tables as $tablekey => $tableItem):?>
    <div class="tab active" data-tabname="<?=$tablekey?>">
        <table class="store-table">
            <thead>
                <tr>
                    <th><span>Артикул</span></th>
                    <th><span>Название</span></th>
                    <th><span>Бренд</span></th>
                    <th><span>Официальный диллер</span></th>
                    <th><span>Крайнее время заказа</span></th>
                    <th><span>Расчетное время доставки</span></th>
                    <th><span>Максимальное время доставки</span></th>
                    <th><span>Склад</span></th>
                    <th><span>В упаковке</span></th>
                    <th><span>Доступное кол-во</span></th>

                    <th><span>Цена</span></th>
                    <th><span>Количество</span></th>
                    <th class="add">купить</th>
                </tr>
            </thead>
            <tbody>
                <?foreach($tableItem as $arkey => $arItem):?>
                    <tr class="detail"
                        data-deliveries="<?=$post["deliveries"]?>"
                        data-code="<?=$arItem["code"];?>"
                        data-brand="<?=$arItem["brand"];?>"
                        data-offer_key="<?=$arItem["offer_key"];?>"
                        data-warehouse_key="<?=$arItem["warehouse_key"];?>"
                        >
                        <td><?=$arItem["code"]?></td>
                        <td><?=$arItem["name"]?></td>
                        <td><?=$arItem["brand"]?></td>
                        <td><?=($arItem["dealer"])? "Да" : "Нет" ;?></td>
                        <td><?=$arItem["order_before"]?></td>
                        <td><?=$arItem["delivery_time"]?></td>
                        <td><?=$arItem["delivery_time_max"]?></td>
                        <td><?=($arItem["warehouse_name"]) ? $arItem["warehouse_name"] : "Заказной";?></td>
                        <td><?=$arItem["packing"]?></td>
                        <td><?=$arItem["amount"]?></td>
                        <td><?=($arItem["CalculatedPrice"]!="") ? $arItem["CalculatedPrice"] : $arItem["price"] ;?> <?=$arItem["currency"]?></td>
                        <td>
                            <div class="counter counter--size_s">
                                <div class="counter__btn" data-counter-btn="minus"> -</div>
                                <input class="counter__input edit_quantity" type="number" name="count" value="1" min="1" max="<?=$arItem["amount"]?>">
                                <div class="counter__btn" data-counter-btn="plus"> +</div>
                            </div>
                        </td>
                        <td>
                            <a class="btn add2basket btn--bg btn--icon btn--size_s">
                                <i class="btn__icon icon">
                                    <svg height="24" width="24" aria-hidden="true">
                                        <use xlink:href="<?= SITE_TEMPLATE_PATH ?>/img/icons/ui/sprite.svg#in_basket"></use>
                                    </svg>
                                </i>
                            </a>
                        </td>
                    </tr>
                <?endforeach;?>
            </tbody>
        </table>
    </div>
<?endforeach;?>