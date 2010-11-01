<?php echo $this->menu; ?>
<?php echo $this->notify; ?>

<?php echo $this->formbox; ?>

<?php if (!empty($this->replying)): ?>

<br class="spacer" />
<h1 class="header">
 <?php echo $this->message_subject; ?>
</h1>

<table style="padding: 2px; width: 100%" class="item">
 <tr>
  <td style="width: 10%" valign="top">
   <?php if (!empty($this->message_author_avatar)): ?>
    <img src="<?php echo $this->message_author_avatar; ?>" alt="<?php echo $this->message_author; ?>" />
    <br />
   <?php endif; ?>
   <?php echo $this->message_author; ?>
  </td>
  <td style="width: 90%" valign="top" class="box">
   <?php echo $this->message_body; ?>
  </td>
 </tr>
</table>

<?php endif; ?>
