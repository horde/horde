<?php foreach ($this->threads_list as $k1 => $v1): ?>

<div class="messageContainer" style="margin-left: <?php echo $v1['indent']; ?>0px">

<div class="messageAuthor">
 <?php echo $v1['link']; ?><strong><?php echo $v1['message_subject']; ?></strong></a><br />
  <?php echo _('Posted by'); ?> <?php echo $v1['message_author']; ?><br />
  <?php echo _('on: '); ?> <?php echo $v1['message_date']; ?>
  <?php if (!empty($v1['message_author_moderator'])): ?>
   <br /><?php echo _('Moderator'); ?>
  <?php endif; ?>
  <br /> [
  <span class="small">
  <?php $i2 = count($v1['actions']); foreach ($v1['actions'] as $k2 => $v2): ?>
    <?php if (isset($v2)) { echo is_array($v2) ? $k2 : $v2; } elseif (isset($v1['actions'])) { echo $v1['actions']; } ?>
  <?php if (--$i2 != 0) { echo ', '; }; endforeach; ?> ]
  </span>
</div>

<div class="messageBody">
 <p>
  <?php echo $v1['body']; ?>
 </p>
</div>

<br class="clear" />
</div>

<?php endforeach; ?>
