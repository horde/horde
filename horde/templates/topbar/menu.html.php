<?php foreach ($this->rootItems as $item): ?>
    <div class="horde-point-left<?php if ($this->items[$item]['active']): ?>-active<?php endif ?>"></div>
    <ul class="horde-dropdown">
      <li><div class="horde-point-center<?php if ($this->items[$item]['active']): ?>-active<?php endif ?>"><a class="horde-mainnavi<?php if ($this->items[$item]['active']): ?>-active<?php endif ?>" href="<?php echo $this->items[$item]['url'] ?>"<?php if ($this->items[$item]['target']) echo ' target="' . $this->items[$item]['target'] . '"'?>><?php echo $this->h($this->items[$item]['label']) ?></a></div>
<?php if ($this->items[$item]['children']): ?>
<?php echo $this->renderPartial('submenu', array('locals' => array('items' => $this->items[$item]['children']))) ?>
<?php endif ?>
      </li>
    </ul>
<?php if ($this->items[$item]['children']): ?>
    <div class="horde-point-arrow<?php if ($this->items[$item]['active']): ?>-active<?php endif ?>"></div>
<?php endif ?>
    <div class="horde-point-right<?php if ($this->items[$item]['active']): ?>-active<?php endif ?>"></div>
<?php endforeach ?>
