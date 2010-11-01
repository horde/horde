<?php echo $this->renderPartial('header'); ?>
<?php echo $this->renderPartial('menu'); ?>

<?php

foreach ($this->list as $test) {
    echo $test;
    echo '<br/>';
}