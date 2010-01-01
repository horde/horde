<?php
$RENDERER->beginActive($Form->getTitle());
$RENDERER->renderFormActive($Form, $vars);
$RENDERER->submit();
$RENDERER->end();
$Form->close($RENDERER);
$deleteUrl = Horde::applicationUrl('extensions.php');
$params = array ('action' => 'delete',
                 'extension' => $extension);
$deleteUrl = Horde_Util::addParameter($deleteUrl, $params);
?>
<a href="<?php echo $deleteUrl; ?>">Delete Extension</a>