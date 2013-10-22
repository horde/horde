<h1 class="header">
 <?php echo _("Mailing List Information") ?>
</h1>

<table class="horde-table mailinglistinfo">
 <tbody>
<?php foreach ($this->headers as $k => $v): ?>
  <tr>
   <td><?php echo $k ?></td>
   <td><?php echo $v ?></td>
  </tr>
<?php endforeach; ?>
 </tbody>
</table>
