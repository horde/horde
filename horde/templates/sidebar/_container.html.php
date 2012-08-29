<?php if ($containerCounter > 0): ?>
<div class="horde-sidebar-split"></div>
<?php endif ?>
<div<?php if (!empty($container['id']) && !is_int($container['id'])) echo ' id="' . $container['id'] . '"' ?>>
<?php if (isset($container['content'])): ?>
<?php echo $container['content'] ?>
<?php elseif (isset($container['rows'])): ?>
<?php echo $this->renderPartial('row', array('collection' => $container['rows'])) ?>
<?php endif ?>
</div>
