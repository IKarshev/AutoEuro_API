<?
/**
 * Добавляем вкладку конструктора наценки в админку
 */
AddEventHandler('main', 'OnBuildGlobalMenu', 'addMenuItem');
function addMenuItem(&$aGlobalMenu, &$aModuleMenu)
{
    global $USER;

    if ($USER->IsAdmin()) {

        $aGlobalMenu['global_menu_custom'] = [
            'menu_id' => 'kontur',
            'text' => 'kontur',
            'title' => 'kontur',
            'url' => 'settingss.php?lang=ru',
            'sort' => 1000,
            'items_id' => 'global_menu_custom',
            'help_section' => 'custom',
            'items' => [
                [
                    'parent_menu' => 'global_menu_custom',
                    'sort'        => 1,
                    'url'         => '/bitrix/admin/price_constructor.php',
                    'text'        => "Конструктор наценки",
                    'title'       => "Конструктор наценки",
                    'icon'        => 'fav_menu_icon',
                    'page_icon'   => 'fav_menu_icon',
                    'items_id'    => 'menu_custom',
                ],
            ],
        ];

    }
}
?>