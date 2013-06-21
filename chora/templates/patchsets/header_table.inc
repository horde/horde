<p class="horde-content">
 <?php echo _("Search Commits:") ?>
 <input type="text" for="patchsets_body" size="20" />
</p>

<table class="horde-table revlog sortable" id="patchsets" cellspacing="0">
<thead>
 <tr>
  <th><?php echo _("Commit") ?></th>
  <th class="sortup"><?php echo _("Date") ?></th>
  <th><?php echo _("Author") ?></th>
  <th class="nosort"><?php echo _("Description") ?></th>
 </tr>
</thead>
<tbody id="patchsets_body">
