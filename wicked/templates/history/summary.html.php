<tr>
 <td><?php echo $this->displayLink ?></td>
<?php if ($this->remove): ?>
 <td style="text-align:center">
  <?php echo $this->deleteLink ?>
 </td>
<?php endif ?>
<?php if ($this->edit): ?>
 <td style="text-align:center">
<?php if (!$this->showRestore): ?>
  <?php echo $this->editLink ?>
<?php endif ?>
 </td>
 <td style="text-align:center">
<?php if ($this->showRestore): ?>
  <?php echo $this->restoreLink ?>
<?php endif ?>
</td>
<?php endif ?>
 <td class="nowrap"><?php echo $this->h($this->author) ?></td>
 <td class="nowrap"><?php echo $this->h($this->date) ?></td>
 <td style="text-align:center"><input type="radio" name="v1" value="<?php echo $this->pversion ?>" /></td>
 <td style="text-align:center"><input type="submit" value="<?php echo $this->version ?>" /></td>

 <td><?php echo $this->h($this->changelog) ?></td>
</tr>
