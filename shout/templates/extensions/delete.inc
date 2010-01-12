Extension: <?php echo $vars->get('extension'); ?><br>
<?php
$RENDERER->beginActive($Form->getTitle());
$RENDERER->renderFormActive($Form, $vars);
$RENDERER->submit();
$RENDERER->end();
$Form->close($RENDERER);
?>
