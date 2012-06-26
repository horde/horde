    <div class="horde-subnavi<?php if ($selected) echo ' horde-subnavi-active' ?>">
      <div class="horde-subnavi-icon-1" style="background-image:url('<?php echo $icon ?>')"><a class="icon" href=""></a></div>
      <div class="horde-subnavi-point"><a href="<?php echo $url ?>"><?php echo $label ?></a></div>
      <div class="clear"></div>
    </div>
<?php if ($children): ?>
    <div class="subfolders">
<?php foreach ($children as $child): ?>
<?php echo $this->renderPartial('row', array('locals' => $this->items[$child])) ?>
<?php endforeach ?>
    </div>
<?php endif ?>
