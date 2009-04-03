<?= $this->renderPartial('header'); ?>
<?= $this->renderPartial('menu'); ?>

<?php
$this->form->renderActive(new Horde_Form_Renderer(), $vars, 'modify', 'post');

