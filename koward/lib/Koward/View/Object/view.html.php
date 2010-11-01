<?php echo $this->renderPartial('header'); ?>
<?php echo $this->renderPartial('menu'); ?>
<?php
if (isset($this->actions)) {
    echo $this->actions->renderActive(new Horde_Form_Renderer(), $this->vars,
                                      $this->post, 'post');
}

if (isset($this->form)) {
    echo $this->form->renderInactive(new Horde_Form_Renderer(), $this->vars);

    if ($this->allowEdit) {
        echo $this->edit;
    }
}
