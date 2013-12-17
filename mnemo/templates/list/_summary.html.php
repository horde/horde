 <tr>
  <td><?php if (isset($summary['edit'])) echo $summary['edit'] ?></td>
<?php if ($this->showNotepad): ?>
  <td><?php echo $this->h($summary['notepad']) ?></td>
<?php endif; ?>
  <td>
   <?php echo $summary['link'] ?>
   <ul class="horde-tags">
<?php foreach ($summary['tags'] as $tag): ?>
    <li><?php echo $this->h($tag) ?></li>
<?php endforeach ?>
   </ul>
  </td>
  <td sortval="<?php echo $summary['modifiedStamp'] ?>">
   <?php echo $summary['modifiedString'] ?>
  </td>
 </tr>
