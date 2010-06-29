<div id="menu">
 <?php echo Hermes::getMenu('string') ?>
</div>
<?php require HORDE_BASE . '/services/portal/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
