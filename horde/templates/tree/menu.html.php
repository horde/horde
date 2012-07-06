<?php foreach ($this->rootItems as $item): ?>
    <div class="horde-navipoint">
      <div class="horde-point-left<?php if ($this->items[$item]['active']): ?>-active<?php endif ?>"></div>
      <ul class="horde-dropdown">
        <li><div class="<?php echo $this->items[$item]['class'] ?>"><?php if (!empty($this->items[$item]['url'])): ?><a class="horde-mainnavi<?php if ($this->items[$item]['active']): ?>-active<?php endif ?>" href="<?php echo $this->items[$item]['url'] ?>"<?php if (!empty($this->items[$item]['target'])) echo ' target="' . $this->items[$item]['target'] . '"'?><?php if (!empty($this->items[$item]['onclick'])) echo ' onclick="' . $this->items[$item]['onclick'] . '"'?>><?php endif ?><?php echo $this->items[$item]['label'] ?><?php if (!empty($this->items[$item]['url'])): ?></a><?php endif ?></div>
<?php if (!empty($this->items[$item]['children'])): ?>
<?php echo $this->renderPartial('submenu', array('locals' => array('items' => $this->items[$item]['children']))) ?>
<?php endif ?>
        </li>
      </ul>
<?php if (!empty($this->items[$item]['children']) && empty($this->items[$item]['noarrow'])): ?>
      <div class="horde-point-arrow<?php if ($this->items[$item]['active']): ?>-active<?php endif ?>"></div>
<?php endif ?>
      <div class="horde-point-right<?php if ($this->items[$item]['active']): ?>-active<?php endif ?>"></div>
    </div>
<?php endforeach ?>
