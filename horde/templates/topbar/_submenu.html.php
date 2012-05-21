        <ul>
<?php foreach ($items as $item): ?>
          <li<?php if (!empty($this->items[$item]['children'])): ?> class="arrow"<?php endif ?>>
            <div class="horde-drowdown-str"><a class="horde-mainnavi" href="<?php echo $this->items[$item]['url'] ?>"<?php if (!empty($this->items[$item]['target'])) echo ' target="' . $this->items[$item]['target'] . '"'?>><?php echo $this->h($this->items[$item]['label']) ?></a></div>
<?php if (!empty($this->items[$item]['children'])): ?>
<?php echo $this->renderPartial('submenu', array('locals' => array('items' => $this->items[$item]['children']))) ?>
<?php endif ?>
          </li>
<?php endforeach ?>
        </ul>
