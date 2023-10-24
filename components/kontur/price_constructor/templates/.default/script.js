$(function(){

    /**
     * Оптимизация select2
     */
    /**/
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
    function init_select2(){
        $('.js-select2').each(function(){
            var items = [];
            var select_id = `#${$(this).attr("id")}`;
            $(this).find("option").each(function(){
                items.push({id: $(this).val(), text: $(this).html()});
            });
            initSelect2(select_id, items);
        });    
    };

    $(".add_rule").on("click", function(event){
        event.preventDefault();
        var LastItem = $(".rule_container").find(".rule_item").last().find(".ID").html();

        var request = BX.ajax.runComponentAction('kontur:price_constructor', 'GetTableItem', {
            mode: 'class',
            data: {
                "LastItem" : LastItem,
            },
            dataType: 'html', // Указываем, что ожидаем HTML-контент
        }).then(function(response) {
            $(".item_cont").append( response.data );
        });

    });


    function ActivateAllFields(item_container){
        $(item_container).find(".filter_global_type, .filter_items, .filter_type, .firts_field, .markup_type, .markup_value").prop("disabled", false);
    };


    $("body").on("change", ".filter_global_type", function(event){
        event.preventDefault();

        var item_container = $(this).closest(".rule_item");
        var ItemCode = $(this).val();

        var request = BX.ajax.runComponentAction('kontur:price_constructor', 'GetItemOptions', {
            mode: 'class',
            data: {
                "ItemCode" : ItemCode,
            },
        }).then(function(response) {
            if( Array.isArray(response.data) ){
                for (var item of response.data) {
                    $(item_container).find(".filter_items").append(`<option value="${item}">${item}</option>`);
                };
            };

            ActivateAllFields( item_container );

            if( ItemCode == "BRAND" ){
                $(item_container).find(".firts_field").prop("disabled", true);
                $(item_container).find(".filter_type").prop("disabled", true);
            };

            if( ItemCode == "PRICE" ){
                $(item_container).find(".filter_items").prop("disabled", true);
            };

        });

    })

    // вешаем флаг "изменен"
    $("body").on("change", ".filter_global_type, .filter_items, .filter_type, .firts_field, .markup_type, .markup_value", function(event){
        event.preventDefault();

        $(this).closest(".rule_item").addClass("edit");
    });

    $("body").on("click", ".delete_item", function(event){
        event.preventDefault();

        var ElementID = $(this).data("item_id");
        var is_new = $(this).hasClass("new");
        var is_delete = false;

        if( !is_new ){
            result = confirm(`Вы уверены, что хотите удалить правило ${ElementID} ?`);
            if( result ){
                var request = BX.ajax.runComponentAction('kontur:price_constructor', 'DeleteSettingsItem', {
                    mode: 'class',
                    data: {
                        "ElementID" : ElementID,
                    },
                }).then(function(response) {
                    is_delete = true;
                });
            };
        }else{
            is_delete = true;
        };

        if( is_delete ){
            $(this).closest(".rule_item").remove();
        };
        
    });


    // Сохраняем настройки
    $(".submit_btn").on("click", function(event){
        event.preventDefault();

        var NewSettings = [];
        $(".rule_container .rule_item.edit").each(function(){
            var rule_item = $(this).closest(".rule_item");
            
            var id = $(rule_item).find(".ID").html();
            var filter_global_type = $(rule_item).find(".filter_global_type").val();
            var filter_items = $(rule_item).find(".filter_items").val();
            var filter_type = $(rule_item).find(".filter_type").val();
            var firts_field = $(rule_item).find(".firts_field").val();
            var markup_type = $(rule_item).find(".markup_type").val();
            var markup_value = $(rule_item).find(".markup_value").val();

            NewSettings.push({
                "ID" : id,
                "FILTER_GLOBAL_TYPE" : filter_global_type,
                "FILTER_ITEMS" : filter_items,
                "FILTER_TYPE" : filter_type,
                "FIRTS_FIELD" : firts_field,
                "MARKUP_TYPE" : markup_type,
                "MARKUP_VALUE" :  markup_value,
            });
        });

        var request = BX.ajax.runComponentAction('kontur:price_constructor', 'SaveSettings', {
            mode: 'class',
            data: {
                "NewSettings" : NewSettings,
            },
        }).then(function(response) {
            $(".button_container .status").html("успех");
            $(".button_container .status").addClass("success");
            $(".button_container .status").show();
            setTimeout(() => {
                $(".button_container .status").hide();
            }, 2500);
        });
    });

});