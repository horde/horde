<?php echo $injector->getInstance('Horde_Core_Topbar')->render(); ?>
<div id="menu">
 <?php echo Operator::getMenu('string') ?>
</div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
