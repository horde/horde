<tr id="rev<?php echo $rev ?>">
 <td>
  <?php if ($diffUrl): ?>
  <a href="<?php echo $diffUrl ?>" class="pdiff" title="<?php echo _("Show changes to the previous revision") ?>"><?php echo Horde::img('diff.png') ?></a>
  <?php endif ?>
  <a href="<?php echo Chora::url('diff', $where, array('r1' => 0, 'r2' => $rev)) ?>" class="sdiff" title="<?php echo _("Show changes to the selected revision") ?>" onclick="Chora_RevLog.revlog_sdiff(this);"><?php echo Horde::img('diff.png') ?></a>
 </td>
 <td><a href="<?php echo $textUrl ?>" title="<?php echo htmlspecialchars($rev) ?>"><?php echo htmlspecialchars($rev_ob->abbrev($rev)) . '</a>'; if ($branchName) echo ' <span class="branch">' . Horde::link(Chora::url('', $where, array('onb' => $branchRev))) . htmlspecialchars($branchName) . '</a></span>'; if (!empty($lg->lines)) echo ' <small>(' . sprintf('%s lines', $lg->lines) . ')</small>'; ?></td>
 <td class="ago" sortval="<?php echo (int)$lg->date ?>"><a title="<?php echo $readableDate ?>"><?php echo $commitDate ?></a></td>
 <td class="author"><?php echo $author ?></td>
 <td class="log">
<?php
echo '<p class="message">' . $logMessage . '</p>';
if ($tags) echo '<p class="tags">' . _("Tags") . ': ' . implode(', ', $tags) . '</p>';
?>
 </td>
</tr>
