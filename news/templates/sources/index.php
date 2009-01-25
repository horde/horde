<table style="width: 100%;" id="sources_list" class="sortable striped">
<thead>
<tr>
<th><?php echo $this->add_url ?></th>
<th><?php echo _("Id") ?></th>
<th><?php echo _("Name") ?></th>
<th><?php echo _("Url") ?></th>
</tr>
</thead>
<?php foreach ($this->sources as $source_id => $source) { ?>
<tr>
<td><?php foreach ($source['actions'] as $action ) { echo $action . ' '; } ?></td>
<td><?php echo $source_id ?></td>
<td><a href="<?php echo News::getUrlFor('source', $source_id) ?>" target="_blank"><?php echo $source['source_name'] ?></a></td>
<td><?php echo $source['source_url'] ?></td>
</tr>
<?php } ?>
</table>
