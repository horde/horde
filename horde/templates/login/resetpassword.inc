<div class="modal-form">
 <?php $notification->notify(array('listeners' => 'status')) ?>
 <?php $form->renderActive($renderer, $vars, 'resetpassword.php', 'post') ?>
</div>

<?php
try {
    echo Horde::loadConfiguration('motd.php', 'motd', null, true);
} catch (Horde_Exception $e) {}
