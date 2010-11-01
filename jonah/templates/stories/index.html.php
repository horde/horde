<?php
/**
 * Template for stories index page - lists available stories
 *
 *   ->stories
 *   ->read
 *   ->comments
 *   ->stories
 */
?>
<?php if (!empty($this->stories)): ?>
<table width="100%" cellspacing="0" class="linedRow nowrap">
 <tr class="item">
  <th width="1%">&nbsp;</th>
  <th class="leftAlign"><?php echo _("Story") ?></th>
  <th class="leftAlign"><?php echo _("Date") ?></th>
  <?php if ($this->read): ?>
    <th class="leftAlign"><?php echo _("Read") ?></th>
  <?php endif; ?>
  <?php if ($this->comments): ?>
    <th class="leftAlign"><?php echo _("Comments") ?></th>
  <?php endif; ?>
 </tr>
 <?php foreach ($this->stories as $story): ?>
   <tr>
    <td>
     <?php echo $story['pdf_link'] ?>
     <?php echo $story['edit_link'] ?>
     <?php echo $story['delete_link'] ?>
    </td>
    <td>
     <?php echo $story['view_link'] ?>
    </td>
    <td>
     <?php echo $story['published_date'] ?>
    </td>
    <?php if ($this->read): ?>
    <td>
     <?php echo $story['readcount'] ?>
    </td>
    <?php endif; ?>
    <?php if ($this->comments): ?>
    <td>
     <?php echo $story['comments'] ?>
    </td>
    <?php endif; ?>
   </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
