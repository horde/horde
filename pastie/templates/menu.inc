<div id="menu">
 <?php echo Pastie::getMenu()->render() ?>
</div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
