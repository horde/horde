<link rel="alternate" title="<?php echo _("Threads") ?>" href="<?php echo $this->rss ?>" type="application/rss+xml" />
<?php echo $this->menu; ?>
<?php echo $this->notify; ?>

<div class="header">
 <?php if (!empty($this->actions)): ?>
  <span class="smallheader rightFloat">
   <?php $i1 = count($this->actions); foreach ($this->actions as $k1 => $v1): ?><a href="<?php echo $v1['url']; ?>" class="smallheader"><?php echo $v1['label']; ?></a><?php if (--$i1 != 0) { echo ', '; }; endforeach; ?>
  </span>
 <?php endif; ?>
 <?php echo $this->forum_name; ?>
</div>
<div class="control">
 <?php echo $this->forum_description; ?>
</div>

<?php if (!empty($this->threads)): ?>
<table style="width:100%; border-collapse:collapse;" class="linedRow">
 <tr class="item">
  <th style="width:50%"<?php echo $this->col_headers['message_subject_class']; ?>>
   <?php echo $this->col_headers['message_subject']; ?>
  </th>
  <th style="width:10%; text-align: center;"<?php echo $this->col_headers['message_seq_class']; ?>>
   <?php echo $this->col_headers['message_seq']; ?>
  </th>
  <th style="width:20%; text-align: center;"<?php echo $this->col_headers['view_count_class']; ?>>
   <?php echo $this->col_headers['view_count']; ?>
  </th>
  <th style="width:20%; text-align: center;"<?php echo $this->col_headers['message_author_class']; ?>>
   <?php echo $this->col_headers['message_author']; ?>
  </th>
  <th style="width:19%"<?php echo $this->col_headers['message_modifystamp_class']; ?>>
   <?php echo $this->col_headers['message_modifystamp']; ?>
  </th>
 </tr>

 <?php foreach ($this->threads as $k2 => $v2): ?>
 <tr>
  <td>
   <?php echo $v2['link']; ?><?php echo $v2['message_subject']; ?></a>
   <?php if (isset($v2['hot'])) { echo $v2['hot']; } ?>
   <?php if (isset($v2['new'])) { echo $v2['new']; } ?>
   <small>
    <?php if (!empty($v2['pages'])): ?><br />[ <?php echo _('Goto page:'); ?> <?php $i3 = count($v2['pages']); foreach ($v2['pages'] as $k3 => $v3): ?><?php if (isset($v3)) { echo is_array($v3) ? $k3 : $v3; } elseif (isset($v2['pages'])) { echo $v2['pages']; } ?><?php if (--$i3 != 0) { echo ', '; }; endforeach; ?> ]<?php endif; ?>
    <?php if (!empty($v2['actions'])): ?><br />[<?php $i4 = count($v2['actions']); foreach ($v2['actions'] as $k4 => $v4): ?><?php if (isset($v4)) { echo is_array($v4) ? $k4 : $v4; } elseif (isset($v2['actions'])) { echo $v2['actions']; } ?><?php if (--$i4 != 0) { echo ', '; }; endforeach; ?> ]<?php endif; ?>
   </small>
  </td>
  <td style="text-align: center;">
   <?php echo $v2['message_seq']; ?>
  </td>
  <td style="text-align: center;">
   <?php echo $v2['view_count']; ?>
  </td>
  <td style="text-align: center;">
   <?php echo $v2['message_author']; ?>
  </td>
  <td>
   <?php if (!empty($v2['message_url'])): ?>
    <?php echo $v2['message_url']; ?><?php echo $v2['last_message_date']; ?></a><br />
    <?php echo _('by'); ?>
    <?php echo $v2['last_message_author']; ?>
   <?php endif; ?>
  </td>
 </tr>
 <?php endforeach; ?>
</table>
 <?php echo $this->pager_link; ?>
<?php else: ?>
 <p><em><?php echo _('There are no threads in this forum.'); ?></em></p>

<?php endif; ?>
