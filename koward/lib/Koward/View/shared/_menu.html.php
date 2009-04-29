<div id="menu">
 <?= $this->menu->render(); ?>
</div>
<?php $this->koward->notification->notify(array('listeners' => 'status')) ?>

