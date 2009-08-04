<div id="menu">
 <?php echo Kronolith::getMenu()->render() ?>
</div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
