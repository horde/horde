<form method="post" id="messages" name="messages" action="<?php echo $this->mailbox_url ?>">
 <?php echo $this->hiddenFieldTag('mailbox', $this->mailbox) ?>
 <?php echo $this->hiddenFieldTag('mailbox_token', $this->mailbox_token) ?>
 <?php echo $this->hiddenFieldTag('page', $this->page) ?>
 <?php echo $this->hiddenFieldTag('actionID') ?>
 <?php echo $this->hiddenFieldTag('targetMbox') ?>
 <?php echo $this->hiddenFieldTag('newMbox', 0) ?>
 <?php echo $this->hiddenFieldTag('flag') ?>
 <?php echo $this->hiddenFieldTag('filter') ?>
 <?php echo $this->forminput ?>
