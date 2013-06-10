<tr>
 <td>
  <?php echo $page->name ?>
<?php if (!empty($page->context)): ?>
  <small>(<?php echo $page->context ?>)</small>
<?php endif ?>
 </td>
 <td class="nowrap"><?php echo $page->version ?></td>
 <td class="nowrap"><?php echo $this->h($page->author) ?></td>
 <td class="nowrap" sortval="<?php echo (int)$page->timestamp ?>"><?php echo $page->date ?></td>
<?php if ($this->hits): ?>
 <td class="nowrap"><?php echo (int)$page->hits ?></td>
<?php endif ?>
</tr>
