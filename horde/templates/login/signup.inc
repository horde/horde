<div class="modal-form">
 <?php $notification->notify(array('listeners' => 'status')) ?>
 <?php $formsignup->renderActive($formsignup->getRenderer(), $vars, Horde::url('signup.php'), 'post') ?>
</div>

<?php
try {
    echo Horde::loadConfiguration('motd.php', 'motd', null, true);
} catch (Horde_Exception $e) {}
