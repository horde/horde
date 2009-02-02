<tr id="rev<?php echo $rev ?>">
 <td>
  <?php if ($diffUrl): ?>
  <a href="<?php echo $diffUrl ?>" class="pdiff" title="<?php echo _("Show changes to the previous revision") ?>"><?php echo Horde::img('diff.png') ?></a>
  <?php endif ?>
  <a href="<?php echo Chora::url('diff', $where, array('r1' => 0, 'r2' => $rev)) ?>" class="sdiff" title="<?php echo _("Show changes to the selected revision") ?>" onclick="Chora_RevLog.sdiff(this);"><?php echo Horde::img('diff.png') ?></a>
 </td>
 <td><a href="<?php echo $textUrl ?>" title="<?php echo htmlspecialchars($rev) ?>"><?php echo htmlspecialchars($VC->abbrev($rev)) ?></a>
<?php foreach (array_diff($branch_info, array($onb)) as $val): ?>
  <span class="branch"><?php echo Horde::link(Chora::url('browse', $where, array('onb' => $val))) . htmlspecialchars($val) ?></a></span>
<?php endforeach; ?>
<?php if (!empty($lg->lines)): ?>
  <small>(<?php printf('%s lines', $lg->lines) ?>)</small>
<?php endif; ?>
</td>
 <td class="ago" sortval="<?php echo (int)$lg->queryDate() ?>"><a title="<?php echo $readableDate ?>"><?php echo $commitDate ?></a></td>
 <td class="author"><?php echo $author ?></td>
 <td class="log">
  <p class="message"><?php echo $logMessage ?></p>
<?php if ($tags): ?>
  <p class="tags"><?php echo _("Tags") . ': ' . implode(', ', $tags) ?></p>
<?php endif; ?>
 </td>
</tr>
