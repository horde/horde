Device: <?php echo $vars->get('devid'); ?><br>
<?php
$RENDERER->beginActive($Form->getTitle());
$RENDERER->renderFormActive($Form, $vars);
$RENDERER->submit();
$RENDERER->end();
$Form->close($RENDERER);
?>
