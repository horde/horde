<div id="menu">
 <div class="leftFloat">
<?php
if (!(isset($menu) && ($menu instanceof Horde_Menu))) {
    $menu = new Horde_Menu();
    $status = $GLOBALS['registry']->get('status', 'horde');
    if ($GLOBALS['registry']->getApp() == 'horde' &&
        $status != 'notoolbar' && $status != 'hidden' && $status != 'inactive') {
        $menu->add(Horde::applicationUrl('services/portal/'), _("_Home"), 'horde.png', $GLOBALS['registry']->getImageDir('horde'));
    }
}
echo $menu->render();
?>
 </div>
</div>
