<?php
$Form->open($RENDERER, $vars, Horde::applicationUrl('extensions.php'), 'post');
$deleteUrl = Horde::applicationUrl('extensions.php');
$params = array ('action' => 'delete',
                 'extension' => $extension);
$deleteUrl = Horde_Util::addParameter($deleteUrl, $params);
?>
<a href="<?php echo $deleteUrl; ?>">Delete Extension</a>
