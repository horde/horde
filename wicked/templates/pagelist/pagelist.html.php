<?php foreach ($this->pages as $page): ?>
<tr>
 <td>
  <?php echo $page['name'] ?>
<?php if (!empty($page['context'])): ?>
  <small>(<?php echo $page['context'] ?>)</small>
<?php endif ?>
 </td>
 <td class="nowrap">
  <?php echo $page['version'] ?>
 </td>
 <td class="nowrap"><?php echo $this->h($page['author']) ?></td>
 <td class="nowrap" sortval="<?php echo $page['timestamp'] ?>"><?php echo $page['created'] ?></td>
<?php if (!empty($page['hits'])): ?>
  <td class="nowrap"><?php echo $page['hits'] ?></td>
<?php endif ?>
</tr>
<?php endforeach ?>
