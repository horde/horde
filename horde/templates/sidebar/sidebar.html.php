<div id="horde-sidebar">

<?php if ($this->newLink): ?>
  <div class="horde-new">
    <div class="horde-new-plus"><?php echo $this->newLink ?></a></div>
    <div class="horde-new-str"><p class="p17 white bold relief"><?php echo $this->newText ?></p></div>
<?php if ($this->newRefresh): ?>
    <div class="horde-new-refresh"><?php echo $this->newRefresh ?></a></div>
    <div class="horde-new-split"></div>
<?php endif ?>
    <div class="clear"></div>
  </div>
<?php endif ?>

<?php if ($this->containers): ?>
<?php echo $this->renderPartial('container', array('collection' => $this->containers)) ?>
<?php elseif (strlen($this->content)): ?>
<?php echo $this->content ?>
<?php endif ?>

</div>
