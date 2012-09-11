<?php echo $GLOBALS['injector']->getInstance('Horde_View_Topbar')->render(); ?>
 <div class="rightFloat">
  <?php echo Chora::repositories() ?>
 </div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
