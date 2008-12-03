<table style="width: 100%;" id="categories_list" class="sortable striped">
<thead>
<tr>
<th><?php echo $this->add_url ?></th>
<th><?php echo _("Id") ?></th>
<th><?php echo _("Name") ?></th>
<th><?php echo _("Parent") ?></th>
<th><?php echo _("Description") ?></th>
</tr>
</thead>
<?php foreach ($this->categories as $category_id => $category) { ?>
<tr>
<td><?php foreach ($category['actions'] as $action ) { echo $action . ' '; } ?></td>
<td><?php echo $category_id ?></td>
<td><?php echo $category['category_name'] ?></td>
<td>
<?php 
if ($category['category_parentid']) {
    echo $this->categories[$category['category_parentid']]['category_name'] .
         ' - '  . $category['category_parentid'];
}
?></td>
<td><?php echo $category['category_description'] ?></td>
</tr>
<?php } ?>

</table>