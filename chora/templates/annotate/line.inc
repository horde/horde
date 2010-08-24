 <tr id="l<?php echo $lineno ?>">
  <td class="rightAlign">
    <?php echo $lineno ?>
  </td>
  <td class="author">
    <?php echo $author ?>
  </td>
  <td class="rev">
    <a href="<?php echo Chora::url('co', $where, array('r' => $rev)) ?>" title="<?php echo htmlspecialchars($rev) ?>"><?php echo htmlspecialchars($VC->abbrev($rev)) ?></a>
  </td>
  <td class="rev">
<?php if ($prev): ?>
    <a href="<?php echo Chora::url('diff', $where, array('r1' => $prev, 'r2' => $rev)) ?>" title="<?php echo sprintf(_("Diff to %s"), htmlspecialchars($prev)) ?>"><?php echo Horde::img('diff.png') ?></a>
    <span class="logdisplay" rev="<?php echo htmlspecialchars($rev) ?>"><?php echo Horde::img('log.png') ?></span>
<?php endif; ?>
  </td>
  <td class="rev">
<?php if ($prev): ?>
    <a href="<?php echo Chora::url('annotate', $where, array('rev' => $prev)) . '#l' . $lineno ?>" title="<?php echo htmlspecialchars($prev) ?>"><?php echo htmlspecialchars($VC->abbrev($prev)) ?></a>
<?php endif; ?>
  </td>
  <td class="item<?php echo $style ?>">
   <tt><?php echo (trim($line) != '') ? $line : '&nbsp;' ?></tt>
  </td>
 </tr>
