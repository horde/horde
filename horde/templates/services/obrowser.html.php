<div class="header">
 <span class="rightFloat">
  <a href="#" onclick="window.close(); return false;"><?php echo $this->hordeImage('close.png') ?></a>
 </span>
 <?php echo _("Object Browser") ?>
</div>

<div class="headerbox">
 <table class="striped" cellspacing="0" style="width:100%">
<?php foreach ($this->rows as $r): ?>
  <tr>
   <td>
    <?php echo $r['icon'] ?>
    <?php echo $r['name'] ?>
   </td>
  </tr>
<?php endforeach; ?>
 </table>
</div>
