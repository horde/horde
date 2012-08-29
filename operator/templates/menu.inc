<?php echo $GLOBALS['injector']->getInstance('Horde_View_Topbar')->render(); ?>
<?php echo Operator::getMenu('string') ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
