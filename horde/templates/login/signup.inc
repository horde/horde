<div class="modal-form">
 <?php $notification->notify(array('listeners' => 'status')) ?>
 <?php $formsignup->renderActive($formsignup->getRenderer(), $vars, Horde::url('signup.php'), 'post') ?>
</div>

<?php
try {
    $config = new Horde_Registry_Loadconfig('horde', 'motd.php');
    echo $config->output;
} catch (Horde_Exception $e) {}
