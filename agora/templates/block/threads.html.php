<table class="linedRow" cellspacing="0" style="width:100%">
<thead>
 <tr class="item nowrap">
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
<tbody>
<?php if (!empty($this->threads)): ?>
<?php foreach ($this->threads as $k1 => $v1): ?>
 <tr>
  <td>
   <?php echo $v1['link']; ?><?php echo $v1['message_subject']; ?></a>
  </td>
  <td>
   <?php echo $v1['message_author']; ?>
  </td>
  <td>
   <?php echo $v1['message_date']; ?>
  </td>
 </tr>
<?php endforeach; ?>
<?php else: ?>
 <tr>
  <td colspan="4" class="control" align="center">
   <?php echo _('No threads'); ?>
  </td>
 </tr>
<?php endif; ?>
</tbody>
</table>
