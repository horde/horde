<?php
$Form->renderActive($RENDERER, $vars, Horde::url('admin/accounts.php'), 'post');

if ($vars->get('action') == 'edit') {
    $deleteUrl = Horde::url('admin/accounts.php')->add(array('action' => 'delete', 'account' => $account));
    echo '<a href="' . $deleteUrl . '">Delete Account</a>';
}
