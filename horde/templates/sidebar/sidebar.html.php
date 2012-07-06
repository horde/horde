<div id="horde-sidebar" style="width:<?php echo $this->width ?>px">

<?php if ($this->newLink): ?>
  <div class="horde-new">
<?php if ($this->newRefresh): ?>
    <div class="horde-new-refresh"><?php echo $this->newRefresh ?>&nbsp;</a></div>
    <div class="horde-new-split"></div>
<?php endif ?>
    <span class="horde-new-link"><?php echo $this->newLink ?><?php echo $this->newText ?></a></span>
  </div>
<?php endif ?>

<?php if ($this->containers): ?>
<?php echo $this->renderPartial('container', array('collection' => $this->containers)) ?>
<?php elseif (strlen($this->content)): ?>
<?php echo $this->content ?>
<?php endif ?>

</div>

<div id="horde-slideleft" class="horde-splitbar-vert" style="left:<?php echo $this->width ?>px">
  <div id="horde-slideleftcursor" class="horde-splitbar-vert-handle"></div>
</div>
<div id="horde-body" style="left:<?php echo $this->width ?>px">
