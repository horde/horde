<?php echo $this->renderPartial('header'); ?>
<?php echo $this->renderPartial('menu'); ?>
<?php echo $this->form->renderActive(new Horde_Form_Renderer(), $this->vars,
                              $this->post, 'post'); ?>