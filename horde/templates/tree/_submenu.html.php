        <ul>
<?php foreach ($items as $item): ?>
          <li<?php if (!empty($this->items[$item]['children'])): ?> class="arrow"<?php endif ?>>
            <div class="horde-drowdown-str"><?php if (!empty($this->items[$item]['url'])): ?><a class="horde-mainnavi" href="<?php echo $this->items[$item]['url'] ?>"<?php if (!empty($this->items[$item]['target'])) echo ' target="' . $this->items[$item]['target'] . '"'?><?php if (!empty($this->items[$item]['onclick'])) echo ' onclick="' . htmlspecialchars($this->items[$item]['onclick']) . '"'?>><?php endif ?><?php echo htmlspecialchars($this->items[$item]['label']) ?><?php if (!empty($this->items[$item]['url'])): ?></a><?php endif ?></div>
<?php if (!empty($this->items[$item]['children'])): ?>
<?php echo $this->renderPartial('submenu', array('locals' => array('items' => $this->items[$item]['children']))) ?>
<?php endif ?>
          </li>
<?php endforeach ?>
        </ul>
