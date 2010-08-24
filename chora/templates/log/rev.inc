<tr id="rev<?php echo $rev ?>">
 <td>
  <?php if ($diffUrl): ?>
  <a href="<?php echo $diffUrl ?>" class="pdiff" title="<?php echo _("Show changes to the previous revision") ?>"><?php echo $diff_img ?></a>
  <?php endif ?>
  <a href="<?php echo Chora::url('diff', $where, array('r1' => 0, 'r2' => $rev)) ?>" class="sdiff" title="<?php echo _("Show changes to the selected revision") ?>" onclick="Chora_RevLog.sdiff(this);"><?php echo $diff_img ?></a>
 </td>
 <td><a href="<?php echo $textUrl ?>" title="<?php echo htmlspecialchars($rev) ?>"><?php echo htmlspecialchars($VC->abbrev($rev)) ?></a>
<?php $branchlist = array_diff($branch_info, array($onb)); if (!empty($branchlist)): ?>
  <span class="branch">
<?php foreach ($branchlist as $val): ?>
  <?php echo Chora::url('browsefile', $where, array('onb' => $val))->link() . htmlspecialchars($val) ?></a>
<?php endforeach; endif; ?>
<?php if (!empty($changedlines)): ?>
  <small class="difflines">(<?php printf('%s lines', htmlspecialchars($changedlines)) ?>)</small>
<?php elseif (!is_null($added)): ?>
  <small class="difflines">(<span class="diffadd">+<?php echo $added ?></span>, <span class="diffdel">-<?php echo $deleted ?></span> <?php echo _("lines") ?>)</small>
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
