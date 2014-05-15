<div class="modal-form">
 <?php $notification->notify(array('listeners' => 'status')) ?>
 <?php $form->renderActive($renderer, $vars, Horde::url('services/resetpassword.php'), 'post') ?>
</div>

<?php
try {
    echo $registry->loadConfigFile('motd.php', 'motd', 'horde')->config['motd'];
} catch (Horde_Exception $e) {}
