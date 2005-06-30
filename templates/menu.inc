<div id="menu">
    <?php echo Shout::getMenu('string') ?>
</div>
<br />
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>