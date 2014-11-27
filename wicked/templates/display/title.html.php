<div class="header">
 <div class="smallheader rightFloat navigation">
  <?php echo $this->navigation($this->name) ?>
 </div>
<?php if (!$this->referrer): ?>
 <?php echo $this->breadcrumb($this->name) ?>
<?php else: ?>
 <?php echo $this->h($this->name) ?>: <?php echo $this->referrer ?>
<?php endif ?>
<?php if ($this->isOld): ?>
 <?php echo $this->h($this->version) ?>
<?php endif ?>
<?php if ($this->locked): ?>
 <?php echo $this->locked ?>
<?php endif ?>
</div>
