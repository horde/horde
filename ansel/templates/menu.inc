<div id="menu">
 <?php echo Ansel::getMenu('string') ?>
</div>

<?php
$GLOBALS['notification']->notify(array('listeners' => 'status'));
