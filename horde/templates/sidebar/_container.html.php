<?php if ($containerCounter > 0): ?>
<div class="horde-sidebar-split"></div>
<?php endif ?>

<?php if (isset($container['header'])): ?>
<h3>
<?php if (isset($container['header']['add'])): ?>
<?php if (is_string($container['header']['add'])): ?>
  <?php echo $container['header']['add'] ?>
<?php else: ?>
  <a href="<?php echo $container['header']['add']['url'] ?>" class="horde-add" title="<?php echo $container['header']['add']['label'] ?>">+</a>
<?php endif ?>
<?php endif ?>
  <span id="<?php echo $container['header']['id'] ?>" class="<?php echo empty($container['header']['collapsed']) ? 'horde-collapse' : 'horde-expand' ?>" title="<?php echo empty($container['header']['collapsed']) ? _("Collapse") : _("Expand") ?>"><?php echo $this->h($container['header']['label']) ?></span>
</h3>
<?php endif ?>

<div<?php if (!empty($container['id']) && !is_int($container['id'])) echo ' id="' . $container['id'] . '"'; if (isset($container['header']) && !empty($container['header']['collapsed'])) echo ' style="display:none"' ?>>
<?php if (isset($container['content'])): ?>
<?php echo $container['content'] ?>
<?php elseif (isset($container['rows'])): ?>
<?php if (empty($container['type']) || $container['type'] == 'tree'): ?>
<?php echo $this->renderPartial('rowtree', array('collection' => $container['rows'])) ?>
<?php else: ?>
<div class="horde-resources">
<?php echo $this->renderPartial('rowresource', array('collection' => $container['rows'])) ?>
</div>
<?php endif ?>
<?php else: ?>
<div class="horde-info"><?php echo _("No items to display") ?></div>
<?php endif ?>
</div>
