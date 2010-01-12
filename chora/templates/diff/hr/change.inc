<tr>
<?php if (!empty($left)): ?>
 <td class="modified">
  <pre><?php echo $left ?></pre>
 </td>
<?php elseif ($row < $oldsize): ?>
 <td class="modified">&nbsp;</td>
<?php else: ?>
 <td class="unmodified">&nbsp;</td>
<?php endif; ?>
<?php if (!empty($right)): ?>
 <td class="modified">
  <pre><?php echo $right ?></pre>
 </td>
<?php elseif ($row < $newsize): ?>
 <td class="modified">&nbsp;</td>
<?php else: ?>
 <td class="unmodified">&nbsp;</td>
<?php endif; ?>
</tr>
