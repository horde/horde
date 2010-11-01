<div id="menu">
 <?php echo Hermes::getMenu('string') ?>
</div>
<?php require HORDE_BASE . '/services/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
