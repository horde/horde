<tr>
 <td class="nowrap">
  <?php echo $this->displayLink ?>
 </td>
 <td class="nowrap">
  <?php echo $this->versionLink ?>
 </td>
 <td class="nowrap"><?php echo $this->h($this->author) ?></td>
 <td class="nowrap" sortval="<?php echo $this->timestamp ?>"><?php echo $this->date ?></td>
<?php if ($this->hits): ?>
 <td class="nowrap"><?php echo $this->hits ?></td>
<?php endif ?>
</tr>
