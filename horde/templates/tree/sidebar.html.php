<?php foreach ($this->rootItems as $root): ?>
<?php echo $this->renderPartial('row', array('locals' => $this->items[$root])) ?>
<?php endforeach ?>
