<div id="menu">
 <?php echo SAM::getMenu('string') ?>
</div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
