<?php foreach ($this->messages as $v): ?>
 <tr id="row<?php echo $v['buid'] ?>"<?php if ($v['class']): ?> class="<?php echo $v['class'] ?>"<?php endif; ?><?php if ($v['bg']): ?> style="background-color:<?php echo $v['bg'] ?>"<?php endif; ?>>
  <td>
   <label>
    <input type="checkbox" class="checkbox" name="buid[]" value="<?php echo $this->h($v['buid']) ?>" />
    <?php echo $v['status'] ?>
   </label>
  </td>
  <td>
   <?php echo $v['from'] ?>
  </td>
  <td>
   <?php echo $v['subject'] ?>
  </td>
  <td>
   <?php echo $this->h($v['date']) ?>
  </td>
  <td class="rightAlign">
   <?php echo $this->h($v['size']) ?>
  </td>
 </tr>
<?php endforeach; ?>
</table>
