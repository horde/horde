<?php echo $injector->getInstance('Horde_Core_Topbar')->render(); ?>
<div id="menu">
 <?php echo Folks::getMenu()->render() ?>
</div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
