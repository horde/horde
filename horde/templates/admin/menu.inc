<div id="menu">
 <div class="leftFloat">
<?php
    $menu = new Horde_Menu(Horde_Menu::MASK_NONE);
    try {
        foreach ($registry->callByPackage('horde', 'admin_list') as $val) {
            $img = isset($val['icon'])
                ? $registry->getImageDir() . '/' . $val['icon']
                : $registry->get('icon');
            $menu->add(strval(Horde::url($registry->applicationWebPath($val['link']))), $val['name'], $img, '');
        }
    } catch (Horde_Exception $e) {}

    echo $menu->render();
?>
 </div>
</div>
<?php $notification->notify(array('listeners' => 'status'));
