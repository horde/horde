<div class="options">
 <span>
  <?php echo _("Search Commits:") ?>
  <input type="text" for="patchsets_body" size="20" />
 </span>
</div>

<table class="revlog striped sortable" id="patchsets" cellspacing="0">
<thead>
 <tr class="item leftAlign">
  <th><?php echo _("Commit") ?></th>
  <th class="sortup"><?php echo _("Date") ?></th>
  <th><?php echo _("Author") ?></th>
  <th class="nosort"><?php echo _("Description") ?></th>
 </tr>
</thead>
<tbody id="patchsets_body">
