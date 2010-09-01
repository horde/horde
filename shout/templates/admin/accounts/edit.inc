<?php
$Form->renderActive($RENDERER, $vars, Horde::url('admin/accounts.php'), 'post');

if ($vars->get('action') == 'edit') {
    $deleteUrl = Horde::url('admin/accounts.php');
    $params = array ('action' => 'delete',
                     'account' => $account);
    $deleteUrl = Horde_Util::addParameter($deleteUrl, $params);
    echo '<a href="' . $deleteUrl . '">Delete Account</a>';
}
