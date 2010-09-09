<?php echo $this->menu; ?>
<?php echo $this->notify; ?>

<h1 class="header">
 <?php if (!empty($this->actions)): ?>
  <span class="smallheader rightFloat">
    <?php $i1 = count($this->actions); foreach ($this->actions as $k1 => $v1): ?><?php if (isset($v1)) { echo is_array($v1) ? $k1 : $v1; } elseif (isset($this->actions)) { echo $this->actions; } ?><?php if (--$i1 != 0) { echo ' | '; }; endforeach; ?></span>
 <?php endif; ?>
 <?php echo _('Forums'); ?>
</h1>
<?php if (!empty($this->forums_list) || !empty($this->forums_list)): ?>
<table class="linedRow" style="width:100%; border-collapse:collapse;">
 <tr class="item">
  <th style="width:75%"<?php echo $this->col_headers['forum_name_class']; ?>>
   <?php echo $this->col_headers['forum_name']; ?>
  </th>
  <th style="width:5%; text-align:center"<?php echo $this->col_headers['message_count_class']; ?>>
   <?php echo $this->col_headers['message_count']; ?>
  </th>
  <th style="width:5%; text-align:center"<?php echo $this->col_headers['thread_count_class']; ?>>
   <?php echo $this->col_headers['thread_count']; ?>
  </th>
  <th style="width:20%"<?php echo $this->col_headers['message_timestamp_class']; ?>>
   <?php echo $this->col_headers['message_timestamp']; ?>
  </th>
 </tr>

 <?php foreach ($this->forums_list as $k2 => $v2): ?>
 <tr valign="top">
  <td>
   <strong><?php echo $v2['indent']; ?><?php if (!empty($v2['url'])): ?><a href="<?php echo $v2['url']; ?>"><?php endif; ?><?php echo $v2['forum_name']; ?><?php if (!empty($v2['url'])): ?></a><?php endif; ?></strong>
   <p><?php echo $v2['forum_description']; ?></p>
   <?php if (!empty($v2['actions'])): ?>
   <?php echo _('Options'); ?>:
   <?php $i3 = count($v2['actions']); foreach ($v2['actions'] as $k3 => $v3): ?><?php if (isset($v3)) { echo is_array($v3) ? $k3 : $v3; } elseif (isset($v2['actions'])) { echo $v2['actions']; } ?><?php if (--$i3 != 0) { echo ', '; }; endforeach; ?>
   <?php endif; ?>
   <?php if (!empty($v2['moderators'])): ?>
   <br />
   <?php echo _('Moderators'); ?>:
   <?php $i4 = count($v2['moderators']); foreach ($v2['moderators'] as $k4 => $v4): ?><?php if (isset($v4)) { echo is_array($v4) ? $k4 : $v4; } elseif (isset($v2['moderators'])) { echo $v2['moderators']; } ?><?php if (--$i4 != 0) { echo ', '; }; endforeach; ?>    <?php endif; ?>
  </td>
  <td style="text-align:center">
   <?php echo $v2['message_count']; ?>
  </td>
  <td style="text-align:center">
   <?php echo $v2['thread_count']; ?>
  </td>
  <td>
   <?php if (!empty($v2['last_message_url'])): ?>
   <a href="<?php echo $v2['last_message_url']; ?>"><?php echo $v2['last_message_date']; ?></a><br />
   <?php echo _('by'); ?>
   <?php echo $v2['last_message_author']; ?>
   <?php endif; ?>
  </td>
 </tr>
 <?php endforeach; ?>
</table>
 <?php echo $this->pager_link;?>
<?php else: ?>
 <p><em><?php echo _('No forums have been created.'); ?></em></p>
<?php endif; ?>
