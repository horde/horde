<div class="header">
 <?php echo $this->h($this->name) ?>
<?php if ($this->referrer): ?>
 : <?php echo $this->referrer ?>
<?php endif ?>
<?php if ($this->isOld): ?>
 <?php echo $this->h($this->version) ?>
<?php endif ?>
 <?php echo $this->refresh ?>
<?php if ($this->locked): ?>
 <?php echo $this->locked ?>
<?php endif ?>
</div>
