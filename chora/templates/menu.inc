<div id="menu">
 <div class="rightFloat">
  <?php echo Chora::repositories() ?>
 </div>
 <?php echo Horde::menu(array('menu_ob' => true))->render() ?>
</div>
<?php require HORDE_BASE . '/services/sidebar.php'; ?>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
