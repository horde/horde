<?php echo $GLOBALS['injector']->getInstance('Horde_View_Topbar')->render(); ?>
<?php echo Folks::getMenu()->render() ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
