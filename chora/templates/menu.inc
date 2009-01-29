<div id="menu">
 <div class="rightFloat">
  <?php echo Chora::repositories() ?>
 </div>
 <?php echo Chora::getMenu() ?>
</div>
<?php $GLOBALS['notification']->notify(array('listeners' => 'status')) ?>
