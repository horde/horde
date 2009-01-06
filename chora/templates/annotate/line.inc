 <tr id="l<?php echo $lineno ?>">
  <td class="rightAlign"><?php echo $lineno ?></td>
  <td class="author"><?php echo $author ?></td>
  <td class="rev"><a id="rev_<?php echo $rev . '_' . $i ?>" href="<?php echo Chora::url('co', $where, array('r' => $rev)) ?>" title="<?php echo $rev ?>"><?php echo $rev_ob->abbrev($rev) ?></a></td>
  <td class="rev"><?php if ($prev): ?><a id="prev_<?php echo $prev . '_' . $i ?>" href="<?php echo Chora::url('annotate', $where, array('rev' => $prev)) . '#l' . $lineno ?>" title="<?php echo $prev ?>"><?php echo $rev_ob->abbrev($prev) ?></a><?php else: ?>&nbsp;<?php endif; ?></td>
  <td class="item<?php echo $style ?>"><tt><?php echo (trim($line) != '') ? $line : '&nbsp;' ?></tt></td>
 </tr>
