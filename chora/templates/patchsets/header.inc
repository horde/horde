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

<table class="revlog striped sortable" id="patchsets" cellspacing="0">
<thead>
 <tr class="item leftAlign">
  <th><?php echo _("Patchset") ?></th>
  <th class="sortup"><?php echo _("Date") ?></th>
  <th><?php echo _("Author") ?></th>
  <th class="nosort"><?php echo _("Files") ?></th>
  <th class="nosort"><?php echo _("Log Message") ?></th>
 </tr>
</thead>
<tbody id="patchsets_body">
