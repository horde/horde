<?= $this->renderPartial('header'); ?>
<?= $this->renderPartial('menu'); ?>
<?= $this->form->renderActive(new Horde_Form_Renderer(), $this->vars,
                              $this->post, 'post'); ?>