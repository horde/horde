<?php foreach ($this->threads_list as $k1 => $v1): ?>

<div class="messageContainer">

<div class="messageAuthor">
 <?php echo $v1['link']; ?><strong><?php echo $v1['message_subject']; ?></strong></a>
  <span style="font-size: 0.9em;">
  <?php
  echo sprintf(_("Posted by %s on %s"), $v1['message_author'], $v1['message_date']);
  if (!empty($v1['message_author_moderator'])): ?>
   <?php echo _('Moderator'); ?>
  <?php endif; ?>
 <?php if (!empty($v1['actions'])): ?>
  </span>
  [
  <span class="small">
  <?php $i2 = count($v1['actions']); foreach ($v1['actions'] as $k2 => $v2): ?>
    <?php if (isset($v2)) { echo is_array($v2) ? $k2 : $v2; } elseif (isset($v1['actions'])) { echo $v1['actions']; } ?>
  <?php if (--$i2 != 0) { echo ', '; }; endforeach; ?>
  </span>
  ]
 <?php endif; ?>
</div>

<div class="messageBody">
 <p>
  <?php echo $v1['body']; ?>
 </p>
</div>

<br class="clear" />
</div>

<?php endforeach; ?>
