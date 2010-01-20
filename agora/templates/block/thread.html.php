<?php if (!empty($this->threads_list)): ?>
<table cellspacing="0" class="linedRow nowrap" style="width:100%">
<thead>
 <tr class="item">
  <th style="width:60%"<?php echo $this->col_headers['message_thread_class']; ?>>
   <?php echo $this->col_headers['message_thread']; ?>
  </th>
  <th style="width:20%"<?php echo $this->col_headers['message_author_class']; ?>>
   <?php echo $this->col_headers['message_author']; ?>
  </th>
  <th style="width:20%"<?php echo $this->col_headers['message_timestamp_class']; ?>>
   <?php echo $this->col_headers['message_timestamp']; ?>
  </th>
 </tr>
</thead>
<?php foreach ($this->threads_list as $k1 => $v1): ?>
<tbody>
 <tr class="<?php echo $v1['class']; ?>" valign="top">
  <td>
   <?php echo $v1['link']; ?><?php echo $v1['message_subject']; ?></a>
   <?php if (!empty($this->thread_view_bodies)): ?>
   <table class="item" style="width:100%">
    <tr>
     <td class="box">
      <?php echo $v1['message_body']; ?>
     </td>
    </tr>
   </table>
   <?php endif; ?>
  </td>
  <td>
   <?php echo $v1['message_author']; ?>
  </td>
  <td>
   <?php echo $v1['message_date']; ?>
  </td>
 </tr>
</tbody>
<?php endforeach; ?>
</table>
<?php endif; ?>
