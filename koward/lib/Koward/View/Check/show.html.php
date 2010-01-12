<?= $this->renderPartial('header'); ?>
<?= $this->renderPartial('menu'); ?>

<?php

foreach ($this->list as $test) {
    echo $test;
    echo '<br/>';
}