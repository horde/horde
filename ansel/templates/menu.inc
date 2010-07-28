<div id="menu">
 <?php echo Ansel::getMenu()->render() ?>
</div>
<?php require HORDE_BASE . '/services/sidebar.php'; ?>

<?php
$GLOBALS['notification']->notify(array('listeners' => 'status'));
