<div class="modal-form">
 <?php $notification->notify(array('listeners' => 'status')) ?>
 <?php $formsignup->renderActive($formsignup->getRenderer(), $vars, Horde::url('signup.php'), 'post') ?>
</div>

<?php
try {
    echo $registry->loadConfigFile('motd.php', 'motd', 'horde')->config['motd'];
} catch (Horde_Exception $e) {}
