<div class="modal-form">
 <?php $notification->notify(array('listeners' => 'status')) ?>
 <?php $form->renderActive($renderer, $vars, Horde::url('services/resetpassword.php'), 'post') ?>
</div>

<?php
try {
    echo $registry->loadConfigFile('motd.php', null, 'horde')->output;
} catch (Horde_Exception $e) {}
