<div class="diff-container">
<?php if (!empty($left)): ?>
 <div class="diff-left diff-modified"><pre><?php echo $left ?></pre></div>
<?php elseif ($row < $oldsize): ?>
 <div class="diff-left diff-modified">&nbsp;</div>
<?php else: ?>
 <div class="diff-left diff-unmodified">&nbsp;</div>
<?php endif; ?>

<?php if (!empty($right)): ?>
 <div class="diff-right diff-modified"><pre><?php echo $right ?></pre></div>
<?php elseif ($row < $newsize): ?>
 <div class="diff-right diff-modified">&nbsp;</div>
<?php else: ?>
 <div class="diff-right diff-unmodified">&nbsp;</div>
<?php endif; ?>
</div>
