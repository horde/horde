<table class="linedRow" cellspacing="0" style="width:100%">
<thead>
 <tr class="item nowrap">
  <th<?php echo $this->col_headers['forum_name_class']; ?>>
   <?php echo $this->col_headers['forum_name']; ?>
  </th>
  <th<?php echo $this->col_headers['message_count_class']; ?>>
   <?php echo $this->col_headers['message_count']; ?>
  </th>
  <th<?php echo $this->col_headers['message_subject_class']; ?>>
   <?php echo $this->col_headers['message_subject']; ?>
  </th>
  <th<?php echo $this->col_headers['message_author_class']; ?>>
   <?php echo $this->col_headers['message_author']; ?>
  </th>
  <th<?php echo $this->col_headers['message_timestamp_class']; ?>>
   <?php echo $this->col_headers['message_timestamp']; ?>
  </th>
 </tr>
</thead>
<tbody>
<?php foreach ($this->forums_list as $k1 => $v1): ?>
 <tr>
  <td>
   <?php echo $v1['indent']; ?><?php if (!empty($v1['url'])): ?><a href="<?php echo $v1['url']; ?>"><?php endif; ?><?php echo $v1['forum_name']; ?><?php if (!empty($v1['url'])): ?></a><?php endif; ?>
   <?php if (!empty($v1['actions'])): ?>
    <small>
     [<?php $i2 = count($v1['actions']); foreach ($v1['actions'] as $k2 => $v2): ?><?php if (isset($v2)) { echo is_array($v2) ? $k2 : $v2; } elseif (isset($v1['actions'])) { echo $v1['actions']; } ?><?php if (--$i2 != 0) { echo ', '; }; endforeach; ?>]
    </small>
   <?php endif; ?>
  </td>
  <td>
   <?php echo $v1['message_count']; ?>
  </td>
  <td>
   <?php if (!empty($v1['message_url'])): ?>
   <a href="<?php echo $v1['message_url']; ?>"><?php echo $v1['message_subject']; ?></a>
   <?php endif; ?>
  </td>
  <td>
   <?php echo $v1['message_author'];  ?>
  </td>
  <td>
   <?php echo $v1['message_date']; ?>
  </td>
 </tr>
<?php endforeach; ?>
</tbody>
</table>
