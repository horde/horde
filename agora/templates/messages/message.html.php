<?php echo $this->menu; ?>
<?php echo $this->notify; ?>

<div class="header">
 <?php if (!empty($this->actions)): ?>
  <span class="smallheader rightFloat">
   <?php $i1 = count($this->actions); foreach ($this->actions as $k1 => $v1): ?><?php if (isset($v1)) { echo is_array($v1) ? $k1 : $v1; } elseif (isset($this->actions)) { echo $this->actions; } ?><?php if (--$i1 != 0) { echo ', '; }; endforeach; ?>
  </span>
 <?php endif; ?>
 <?php if (!empty($this->message_subject)): ?><?php echo $this->message_subject; ?><?php else: ?>&nbsp;<?php endif; ?>
</div>

<div class="messageContainer">
 <div class="messageAuthor">
  <?php if (!empty($this->message_author_avatar)): ?>
   <img src="<?php echo $this->message_author_avatar; ?>" alt="<?php echo $this->message_author; ?>" />
   <br />
  <?php endif; ?>
  <?php echo $this->message_author; ?>
  <?php if (!empty($this->message_author_moderator)): ?>
   <br /><?php echo _('Moderator'); ?>
  <?php endif; ?>
 </div>
 <div class="messageBody">
  <p>
   <?php echo $this->message_body; ?>
   <?php if (!empty($this->message_attachment)): ?><br /><?php echo $this->message_attachment; ?><?php endif; ?>
  </p>
 </div>
 <br class="clear" />
</div>

<h1 class="header">
 <?php echo _('Thread Summary'); ?>
</h1>

<div class="item">
 <?php echo $this->threads; ?>
</div>

 <?php echo $this->pager_link; ?>

<br class="spacer" />
<?php echo $this->form; ?>
