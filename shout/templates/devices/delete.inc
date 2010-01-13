Device: <?php echo $vars->get('devid'); ?><br>
<?php
$Form->renderActive($RENDERER, $vars, Horde::applicationUrl('devices.php'), 'post');
?>
