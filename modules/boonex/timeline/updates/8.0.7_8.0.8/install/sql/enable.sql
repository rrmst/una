-- MENUS
UPDATE `sys_menu_items` SET `onclick`='bx_menu_popup(''bx_timeline_menu_item_manage'', this, {''id'':''bx_timeline_menu_item_manage_{content_id}''}, {content_id:{content_id}});' WHERE `set_name`='bx_timeline_menu_item_actions' AND `name`='item-more';