<?php echo $this->menu; ?>
<?php echo $this->notify; ?>

<?php if (!empty($this->searchResults)): ?>
<h1 class="header"><?php echo _('Search Results'); ?> (<?php echo $this->searchTotal; ?>)</h1>
<table style="width: 100%" class="item linedRow" cellspacing="0">
<?php foreach ($this->searchResults as $k1 => $v1): ?>
<tr class="item">
 <th colspan="4" style="width:99%" class="leftAlign item">
  <a href="<?php echo $v1['forum_url']; ?>"><strong><?php echo $v1['forum_name']; ?></strong></a>
 </th>
</tr>
<?php foreach ($v1['messages'] as $k2 => $v2): ?>
<tr class="item">
 <td style="width:5%" class="item">&nbsp;</td>
 <td style="width:54%" class="leftAlign item">
   <a href="<?php echo $v2['message_url']; ?>"><?php echo $v2['message_subject']; ?></a>
 </td>
 <td style="width:20%" class="leftAlign item">
   <strong><?php echo $v2['message_author']; ?></strong>
 </td>
 <td style="width:20%" class="leftAlign item">
   <?php echo $v2['message_date']; ?>
 </td>
</tr>
<?php endforeach; ?>
<?php endforeach; ?>
</table>

<?php echo $this->pager_link; ?>

<br class="spacer" />

<?php endif; ?>

<?php echo $this->searchForm; ?>
