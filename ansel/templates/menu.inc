<div id="menu">
 <?php echo Ansel::getMenu()->render() ?>
</div>

<?php
$GLOBALS['notification']->notify(array('listeners' => 'status'));
