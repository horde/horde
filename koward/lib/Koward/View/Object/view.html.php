<?= $this->renderPartial('header'); ?>
<?= $this->renderPartial('menu'); ?>
<?php
if (isset($this->actions)) {
    echo $this->actions->renderActive(new Horde_Form_Renderer(), $this->vars,
                                      $this->post, 'post');
}

if (isset($this->form)) {
    echo $this->form->renderInactive(new Horde_Form_Renderer(), $this->vars);

    echo $this->edit;
}
