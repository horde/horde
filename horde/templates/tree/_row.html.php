<div class="horde-subnavi<?php if (!empty($selected)) echo ' horde-subnavi-active' ?>">
 <div class="horde-subnavi-icon" style="background-image:url('<?php echo $icon ?>')"><a class="icon" href=""></a></div>
 <div class="horde-subnavi-point">
<?php if (!empty($url)): ?>
  <a href="<?php echo $url ?>">
<?php endif ?>
   <?php echo $label ?>
<?php if (!empty($url)): ?>
  </a>
<?php endif ?>
 </div>
</div>
<?php if (!empty($children)): ?>
<div class="horde-subnavi-sub">
<?php foreach ($children as $child): ?>
<?php echo $this->renderPartial('row', array('locals' => $this->items[$child])) ?>
<?php endforeach ?>
</div>
<?php endif ?>
