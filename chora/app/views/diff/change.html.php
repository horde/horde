<tr>
<?php if (!empty($left)): ?>
 <td class="diff-modified">
  <div class="diff"><pre><?php echo $left ?></pre></div>
 </td>
<?php elseif ($row < $oldsize): ?>
 <td class="diff-modified"></td>
<?php else: ?>
 <td class="diff-unmodified"></td>
<?php endif; ?>
<?php if (!empty($right)): ?>
 <td class="diff-modified">
  <div class="diff"><pre><?php echo $right ?></pre></div>
 </td>
<?php elseif ($row < $newsize): ?>
 <td class="diff-modified"></td>
<?php else: ?>
 <td class="diff-unmodified"></td>
<?php endif; ?>
</tr>
