<table style="width:100%; border-collapse:collapse;" class="item linedRow">
 <tr class="item">
  <th style="width:60%" class="leftAlign">
   <?php echo $this->col_headers['message_thread']; ?>
  </th>
  <th style="width:20%" class="leftAlign">
   <?php echo $this->col_headers['message_author']; ?>
  </th>
  <th style="width:19%" class="leftAlign">
   <?php echo $this->col_headers['message_timestamp']; ?>
  </th>
 </tr>

 <?php foreach ($this->threads_list as $k1 => $v1): ?>
 <tr valign="top" height="20" <?php if (!empty($this->thread_view_bodies)): ?> style="border-bottom:0;" <?php endif; ?>>
  <td>
    <strong><?php echo $v1['link']; ?><?php echo $v1['message_subject']; ?></a></strong>
    <small>
     [ <?php
        if (isset($v1['reply'])) {
            echo $v1['reply'] . ', ';
        }
        if (isset($v1['actions'])) {
            foreach ($v1['actions'] as $v2) {
                echo $v2 . ', ';
            }
        }
    ?> ]
    </small>
  </td>
  <td>
   <?php echo $v1['message_author']; ?>
  </td>
  <td>
   <?php echo $v1['message_date']; ?>
  </td>
 </tr>
 <?php if (!empty($this->thread_view_bodies)): ?>
 <tr class="<?php echo $v1['class']; ?>" valign="top">
  <td colspan="3" style="padding:7px" wrap="virtual">
    <?php echo $v1['body']; ?>
    <br />
    <?php echo $v1['message_attachment']; ?>
    <br />
  </td>
 </tr>
 <?php endif; ?>
 <?php endforeach; ?>

</table>
