<div class="header">
 <span class="smallheader" style="float:right">
    <?php echo $this->count; ?>
 </span>
    <?php echo $this->header; ?>
</div>
<div id="category-select">
<form action="" method="get" name="stockcategoriesmenu">
 <tag:form_input />
 <select name="category_id" onchange="document.stockcategoriesmenu.submit()">
  <option value=""><?php echo _("Show Category:"); ?></option>
    <?php   foreach ($this->allCategories() as $category) {
                $selected = in_array($category->category_id, $this->selectedCategories) ? ' selected="selected" ' : '';
                printf('<option value="%s"%s/>%s</option>',
                            $category->category_id,
                            $selected,
                            $category->category
                       );

            } ?>
 </select>
</form>
</div>

<table id="stock" class="stock sortable striped" cellspacing="0">
<thead>
 <tr class="item leftAlign">
  <th width="1%" class="nosort">&nbsp;</th>
    <?php
    foreach ($this->columnHeaders as $header) {
        printf('  <th id="%s" %s %s>%s</th>',
            $header['id'],
            $header['class'],
            $header['width'],
            $header['link']
        );
    };
    ?>
 </tr>
</thead>
<tbody>
<?php
    foreach ($this->shownStock as $item) {
        print '<tr>';
        foreach ($item['columns'] as $column) {
            printf('<td %s>%s</td>',
                $column['class'],
                $column['column']
            );
        }
        print '</tr>';
    }
?>
</tbody>
</table>