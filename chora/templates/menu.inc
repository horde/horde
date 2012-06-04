<?php echo $injector->getInstance('Horde_View_Topbar')->render(); ?>
<div id="menu">
 <div class="rightFloat">
  <?php echo Chora::repositories() ?>
 </div>
 <?php echo Horde::menu(array('menu_ob' => true))->render() ?>
</div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
