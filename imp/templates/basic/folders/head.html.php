<form id="fmanager" name="fmanager" method="post" action="<?php echo $this->folders_url ?>">
 <?php echo $this->hiddenFieldTag('actionID') ?>
 <?php echo $this->hiddenFieldTag('folders_token', $this->folders_token) ?>
 <?php echo $this->hiddenFieldTag('new_mailbox') ?>
 <?php echo $this->hiddenFieldTag('new_names') ?>
 <?php echo $this->hiddenFieldTag('old_names') ?>
 <?php echo $this->hiddenFieldTag('view_subscribed') ?>
