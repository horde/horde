 <tr>
  <td><?php if (isset($summary['edit'])) echo $summary['edit'] ?></td>
<?php if ($this->showNotepad): ?>
  <td><?php echo $this->h($summary['notepad']) ?></td>
<?php endif; ?>
  <td><?php echo $summary['link'] ?></a></td>
  <td sortval="<?php echo $summary['modifiedStamp'] ?>">
   <?php echo $summary['modifiedString'] ?>
  </td>
  <td class="base-category" style="<?php echo Mnemo::getCssStyle($summary['category']) ?>"><?php echo htmlspecialchars($summary['category'] ? $summary['category'] : _("Unfiled")) ?></td>
 </tr>
