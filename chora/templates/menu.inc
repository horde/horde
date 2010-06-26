<div id="menu">
 <div class="rightFloat">
  <?php echo Chora::repositories() ?>
 </div>
 <?php echo Chora::getMenu()->render() ?>
</div>
<?php require HORDE_BASE . '/services/portal/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
