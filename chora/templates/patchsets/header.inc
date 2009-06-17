<table class="options" cellspacing="0">
 <tr>
  <td>
<?php if ($ps_id): ?>
   <?php echo $full_ps_link ?>
<?php else: ?>
   <?php echo _("Search Patchsets:") ?>
   <input type="text" for="patchsets_body" size="20" />
<?php endif; ?>
  </td>
 </tr>
</table>
