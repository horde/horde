<div id="msgheaders">
 <p><?php echo $this->label ?></p>
 <table>
<?php foreach ($this->headers as $v): ?>
  <tr<?php if (!empty($v['class'])): ?> class="<?php echo $v['class'] ?>"<?php endif; ?>>
   <td class="rightAlign nowrap">
    <?php echo $v['name'] ?>
   </td>
   <td class="msgheader">
    <?php echo $v['val'] ?>
   </td>
  </tr>
<?php endforeach; ?>
 </table>
</div>

<div id="messageBody" class="messageBody">
 <?php echo $this->msgtext ?>
</div>
