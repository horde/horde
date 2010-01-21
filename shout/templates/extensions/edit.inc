<?php
$Form->renderActive($RENDERER, $vars, Horde::applicationUrl('extensions.php'), 'post');

if ($vars->get('action') == 'edit') {
    $deleteUrl = Horde::applicationUrl('extensions.php');
    $params = array ('action' => 'delete',
                     'extension' => $extension);
    $deleteUrl = Horde_Util::addParameter($deleteUrl, $params);
    echo '<a href="' . $deleteUrl . '">Delete Extension</a>';
}
