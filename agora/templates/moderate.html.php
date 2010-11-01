<?php if (!empty($this->menu)): ?>
<?php echo $this->menu; ?>
<?php endif; ?>
<?php echo $this->notify; ?>

<?php if (!empty($this->messages)): ?>

<form name="messages" method="post">
<?php echo $this->session_tag; ?>

<h1 class="header"><?php echo _('Messages Awaiting Moderation'); ?></h1>
<table cellspacing="0" width="100%" class="striped">
<thead>
 <tr class="item">
  <th width="1%">
  </th>
  <th<?php echo $this->col_headers['forum_id_class']; ?>>
   <?php echo $this->col_headers['forum_id']; ?>
  </th>
  <th<?php echo $this->col_headers['message_subject_class']; ?>>
   <?php echo $this->col_headers['message_subject']; ?>
  </th>
  <th<?php echo $this->col_headers['message_author_class']; ?>>
   <?php echo $this->col_headers['message_author']; ?>
  </th>
  <th<?php echo $this->col_headers['message_body_class']; ?>>
   <?php echo $this->col_headers['message_body']; ?>
  </th>
  <th<?php echo $this->col_headers['message_timestamp_class']; ?>>
   <?php echo $this->col_headers['message_timestamp']; ?>
  </th>
 </tr>
</thead>
<tbody>
 <?php foreach ($this->messages as $k1 => $v1): ?>
 <tr style="vertical-align:top">
  <td><input type="checkbox" class="checkbox" name="message_ids[]" value="<?php echo $v1['message_id']; ?>" /></td>
  <td class="nowrap"><?php echo $v1['forum_name']; ?></td>
  <td><?php echo $v1['message_subject']; ?></td>
  <td><?php echo $v1['message_author']; ?></td>
  <td><?php echo $v1['message_body']; ?></td>
  <td class="nowrap"><?php echo $v1['message_date']; ?></td>
 </tr>
 <?php endforeach; ?>
</tbody>
</table>
<p>
 <?php foreach ($this->buttons as $k2 => $v2): ?>
  <input type="submit" class="button" name="action" value="<?php echo $v2 ?>" />
 <?php endforeach; ?>
</p>
</form>

<?php echo $this->pager; ?>

<?php endif; ?>
