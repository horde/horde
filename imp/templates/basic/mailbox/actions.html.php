<div class="horde-buttonbar">
 <ul class="rightFloat">
<?php foreach ($this->mboxactions as $v): ?>
  <li><?php echo $v ?></li>
<?php endforeach; ?>
 </ul>
 <ul>
<?php if ($this->templateedit): ?>
  <li><?php echo $this->templateedit ?></li>
<?php endif; ?>
<?php if ($this->delete): ?>
  <li><?php echo $this->delete ?></li>
<?php endif; ?>
<?php if ($this->undelete): ?>
  <li><?php echo $this->undelete ?></li>
<?php endif; ?>
<?php if ($this->blacklist): ?>
  <li><?php echo $this->blacklist ?></li>
<?php endif; ?>
<?php if ($this->whitelist): ?>
  <li><?php echo $this->whitelist ?></li>
<?php endif; ?>
<?php if ($this->forward): ?>
  <li><?php echo $this->forward ?></li>
<?php endif; ?>
<?php if ($this->redirect): ?>
  <li><?php echo $this->redirect ?></li>
<?php endif; ?>
<?php if ($this->spam): ?>
  <li><?php echo $this->spam ?></li>
<?php endif; ?>
<?php if ($this->notspam): ?>
  <li><?php echo $this->notspam ?></li>
<?php endif; ?>
  <li><?php echo $this->view_messages ?></li>
 </ul>
</div>
