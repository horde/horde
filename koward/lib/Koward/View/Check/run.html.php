<?php echo $this->renderPartial('header'); ?>
<?php echo $this->renderPartial('menu'); ?>

<?php
if (!empty($this->test)) {
    ob_start();
    $listener = new Koward_Test_Renderer();
    PHPUnit_TextUI_TestRunner::run($this->test, array('listeners' => array($listener)));
    echo ob_get_clean();
}