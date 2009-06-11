<div id="menu">
 <?php echo Kastalia::getMenu('string') ?>
</div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
