<?php

$sidebar = $injector->getInstance('Horde_View_Sidebar');
$self = (string)Horde::selfUrl();

try {
    foreach ($registry->callByPackage('horde', 'admin_list') as $method => $val) {
        if ($registry->isAdmin() ||
            $injector->getInstance('Horde_Perms')->hasPermission('horde:administration:' . $method, $registry->getAuth(), Horde_Perms::SHOW)) {
            $url = Horde::url($registry->applicationWebPath($val['link']));
            $sidebar->addRow(array(
                'cssClass' => 'horde-admin-' . $val['icon'],
                'label' => $val['name'],
                'selected' => $self == (string)$url,
                'url' => $url,
            ));
        }
    }
} catch (Horde_Exception $e) {
}

echo $injector->getInstance('Horde_View_Topbar')->render();
echo $sidebar->render();
$notification->notify(array('listeners' => 'status'));
