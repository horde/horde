<?php if ($containerCounter > 0): ?>
<div class="horde-subnavi-split"></div>
<?php endif ?>
<div<?php if (!empty($container['id'])) echo ' id="' . $container['id'] . '"' ?> class="horde-sidebar-<?php if ($containerCounter == $this->containersCount - 1) echo 'sub' ?>folder">
<?php echo $this->renderPartial('row', array('collection' => $container['rows'])) ?>
</div>
