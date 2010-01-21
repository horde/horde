Extension: <?php echo $vars->get('extension'); ?><br>
<?php
$Form->renderActive($RENDERER, $vars, Horde::applicationUrl('extensions.php'), 'post');
