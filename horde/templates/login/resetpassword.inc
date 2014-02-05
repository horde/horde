<div class="modal-form">
 <?php $notification->notify(array('listeners' => 'status')) ?>
 <?php $form->renderActive($renderer, $vars, Horde::url('services/resetpassword.php'), 'post') ?>
</div>

<?php
try {
    $config = new Horde_Registry_Loadconfig('horde', 'motd.php');
    echo $config->output;
} catch (Horde_Exception $e) {}
