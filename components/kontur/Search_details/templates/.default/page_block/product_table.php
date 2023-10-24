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

<div class="tab" data-tabname="similar">
    <div class="product_container">
        <table>

            <thead>
                <tr>
                    <td>Артикул</td>
                    <td>Бренд</td>
                    <td>Название</td>
                    <td>Склад</td>
                    <td>В упаковке</td>
                    <td>Доступное кол-во</td>
                    <td>Цена</td>
                    <td class="add"></td>
                </tr>
            </thead>

            <?foreach ($arResult["similar"] as $arkey => $arItem):?>
                <tr class="detail" data-brand="<?=$arItem["brand"];?>" data-offer_key="<?=$arItem["offer_key"];?>" data-warehouse_key="<?=$arItem["warehouse_key"];?>">
                    <td class="detail">
                        <span class="name"><?=$arItem["code"];?></span><br>
                        <span class="info"><?=($arItem["dealer"])? "Официальный диллер" : "" ;?></span>
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
        </table>
    </div>
</div>