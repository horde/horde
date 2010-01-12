 <tr<?php if ($attic) echo ' class="attic"' ?>>
<?php if (!Chora::isRestricted($fileName)): ?>
  <td>
<?php if ($attic): ?>
   <?php echo Horde::img('deleted.png', _("Deleted File"), 'width="16" height="16"') ?>
<?php else: ?>
   <img src="<?php echo $icon ?>" width="16" height="16" alt="<?php echo _("File") ?>" />
<?php endif; ?>
<?php if ($conf['filename_linkto'] == 'revlog'): ?>
   <a href="<?php echo $url ?>">
<?php else: ?>
   <a href="<?php echo Chora::url('co', $fileName, array('r' => $filerev)) ?>">
<?php endif; ?>
<?php echo $name ?></a>
  </td>
  <td>
   <strong>
<?php if ($conf['filename_linkto'] == 'revlog'): ?>
    <a href="<?php echo Chora::url('co', $fileName, array('r' => $filerev)) ?>" title="<?php echo htmlspecialchars($filerev) ?>">
<?php else: ?>
    <a href="<?php echo $url ?>" title="<?php echo htmlspecialchars($filerev) ?>">
<?php endif; ?>
<?php echo htmlspecialchars($VC->abbrev($filerev)) ?></a></strong>
  </td>
<?php else: ?>
  <td>
<?php if ($attic): ?>
   <?php echo Horde::img('deleted.png', _("Deleted File"), 'width="16" height="16"') ?>
<?php else: ?>
   <img src="<?php echo $icon ?>" width="16" height="16" alt="<?php echo _("File") ?>" />
<?php endif; ?>
   <?php echo $name ?>
  </td>
  <td><strong><?php echo htmlspecialchars($VC->abbrev($filerev)) ?></strong></td>
<?php endif; ?>
  <td><?php echo $author ?></td>
  <td sortval="<?php echo (int)$date ?>">
   <em><?php echo $readableDate ?></em>
  </td>
  <td><?php if (!empty($log)) echo htmlspecialchars($shortLog) ?></td>
 </tr>
