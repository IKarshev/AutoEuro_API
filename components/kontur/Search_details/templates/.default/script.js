BX.ready(function(){
    // поиск деталей
    if(typeof form_search_details !== "undefined"){
        $(`#${form_search_details}`).validate({
            rules: {
                detail_code:{required: true,},
                brand:{required: false,},
                deliveries:{required: true,},
            },
            messages: {
                detail_code:{required: "Необходимо заполнить поле Артикул",},
                brand:{required: "Необходимо заполнить поле бренд",},
                deliveries:{required: "Необходимо выбрать доставку",},
            },
            submitHandler: function(form, event) {
                event.preventDefault();    
        
                const formdata = new FormData( document.getElementById(`${form_search_details}`) );

                // Если жестко задан способ доставки, то поле disable, а formdata не получает его автоматически,
                // поэтому нужно получить его вручную
                if( $("select[name=deliveries]").prop('disabled') ){
                    formdata.append('deliveries', $("select[name=deliveries]").val());
                };
                 
                var request = BX.ajax.runComponentAction('kontur:Search_details', 'GetDetails', {
                    mode: 'class',
                    data: formdata,
                    dataType: 'html', // Указываем, что ожидаем HTML-контент
                }).then(function(response) {
                    $(".tab_container").addClass("active");
                    $("#product_table").html("").html( response.data );
                });
            },
            errorElement: "div",
            errorPlacement: function(error, element) {
                $(`#${form_search_details} .error_placement`).append(error);
            },
        });
        
    }
});
$(function(){

    /**
     * Оптимизация select2
     */
    function initSelect2(selectId, dataSelect){
        var pageSize = 20;
        $.fn.select2.amd.require(["select2/data/array", "select2/utils"], function (ArrayData, Utils) {					
            function CustomData($element, options) {
                CustomData.__super__.constructor.call(this, $element, options);
            }
            
            Utils.Extend(CustomData, ArrayData);
            CustomData.prototype.query = function (params, callback) {
                if (!("page" in params)) {
                    params.page = 1;
                }
                var data = {};
                if (params.term != undefined && params.term != '') {
                    let subData = $.grep(dataSelect, function (n, i) {
                        if (n.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                            return n;
                        }
                    });
                    data.results = subData.slice((params.page - 1) * pageSize, params.page * pageSize);
                }
                else {
                    data.results = dataSelect.slice((params.page - 1) * pageSize, params.page * pageSize);
                }
                data.pagination = {};
                data.pagination.more = params.page * pageSize < dataSelect.length;
                callback(data);
            };
            
            $(`${selectId}`).select2({
                ajax: {},
                dataAdapter: CustomData,
                width: '100%'
            });
        });
    };

    /**
     * Инициализируем select2
     */
    $('.js-select2').each(function(){
        var items = [];
        var select_id = `#${$(this).attr("id")}`;
        $(this).find("option").each(function(){
            items.push({id: $(this).val(), text: $(this).html()});
        });
        initSelect2(select_id, items);
    });


    // Выбор кол-ва добавляемых в корзину товаров
    $('body').on('click', '.number-minus, .number-plus', function(){
        var $row = $(this).closest('.number');
        var $input = $row.find('.number-text');
        var step = $row.data('step');
        var val = parseFloat($input.val());
        if ($(this).hasClass('number-minus')) {
            val -= step;
        } else {
            val += step;
        }
        $input.val(val);
        $input.change();
        return false;
    });
    
    $('body').on('change', '.number-text', function(){
        var $input = $(this);
        var $row = $input.closest('.number');
        var step = $row.data('step');
        var min = parseInt($row.data('min'));
        var max = parseInt($row.data('max'));
        var val = parseFloat($input.val());
        if (isNaN(val)) {
            val = step;
        } else if (min && val < min) {
            val = min;	
        } else if (max && val > max) {
            val = max;	
        }
        $input.val(val);
    });


    // tabs
    $('body').on('click', '.toggle_tab', function(event){
        event.preventDefault();

        if( !$(this).hasClass("active") ){
            $(".toggle_tab").removeClass("active");
            $(this).addClass("active");

            $(".product_table .tab").removeClass("active");
            $(`.product_table .tab[data-tabname=${ $(this).attr("data-tabname") }]`).addClass("active");
        };
    });

    // .clear_basket
    $('body').on('click', '.clear_basket', function(event){
        event.preventDefault();
        var items = $(this).data("items");

        var BasketInfo = BX.ajax.runComponentAction('kontur:Search_details', 'ClearBasket', {
            mode: 'class',
            data: {
                'ItemID' : items,
            },
        }).then(function(response) {
            // console.log(response);
            $.fancybox.close();
        });

    });

    // add2basket
    $('body').on('click', '.add2basket', function(event){
        event.preventDefault();

        var offer_key = $(this).closest("tr.detail").data("offer_key");
        var warehouse_key = $(this).closest("tr.detail").data("warehouse_key");
        var brand = $(this).closest("tr.detail").data("brand");
        var code = $(this).closest("tr.detail").data("code");
        var deliveries = $(this).closest("tr.detail").data("deliveries");
        var quentity = $(this).closest("tr.detail").find("input[name=count]").val();

        // Проверка, есть ли в корзине пользователя обычные товары
        var BasketInfo = BX.ajax.runComponentAction('kontur:Search_details', 'CheckBasketForConflictingProducts', {
            mode: 'class',
            data: {
                'SearchingTypeOfProduct' : 'default',
            },
        }).then(function(response) {

            if( Array.isArray(response["data"]) ){
                // Обычные товары есть, открываем pop-up
                $json = JSON.stringify(response["data"]);

                $.fancybox.open(`
                    <div class="message">
                        <h2>Внимание!</h2>
                        <p>Перед добавлением деталей необходимо очистить корзину</p>
                        <div style="display:flex;flex-direction:row;align-items:center;justify-content:space-around;margin-top:50px;" class="button_cont">
                            <button class="clear_basket" data-items="${$json}">Очистить корзину</button>
                            <button type="button" data-fancybox-close title="close">Отмена</button>
                        </div>
                    </div>
                `);
            } else{
                var request = BX.ajax.runComponentAction('kontur:Search_details', 'ADD2Basket', {
                    mode: 'class',
                    data: {
                        'offer_key' : offer_key,
                        'warehouse_key' : warehouse_key,
                        'deliveries' : deliveries,
                        'quentity' : quentity,
                        'brand' : brand,
                        'code' : code,
                    },
                }).then(function(response) {
                    console.log("Добавляем в корзину:");
                });
            };
        });

    });

});