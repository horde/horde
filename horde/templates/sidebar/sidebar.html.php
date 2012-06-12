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

<?php $i = 0; $c = count($this->containers); foreach ($this->containers as $id => $container): $i++ ?>

<?php if ($i != 1): ?>
  <div class="horde-subnavi-split"></div>
<?php endif ?>

  <div id="<?php echo $id ?>" class="horde-sidebar-<?php if ($i == $c) echo 'sub' ?>folder">
<?php echo $container ?>
  </div>
<?php endforeach ?>

</div>
