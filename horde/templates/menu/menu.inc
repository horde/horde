<?php
    if (!(isset($menu) && ($menu instanceof Horde_Menu))) {
        $menu = new Horde_Menu();
        if (($GLOBALS['registry']->getApp() == 'horde') &&
            !in_array($GLOBALS['registry']->get('status', 'horde'), array('notoolbar', 'hidden', 'inactive'))) {
            $menu->add(Horde::url('services/portal/'), _("_Home"), 'horde.png', Horde_Themes::img(null, 'horde'));
        }
    }
?>
<div id="menu">
 <div class="leftFloat"><?php echo $menu->render(); ?></div>
</div>
<?php require HORDE_BASE . '/services/sidebar.php'; ?>
