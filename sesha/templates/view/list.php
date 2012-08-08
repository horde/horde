<div class="header">
 <span class="smallheader" style="float:right">
  <?php echo $this->count ?>
 </span>
 <?php echo $this->header ?>
</div>
<div id="category-select">
<form action="" method="get" name="stockcategoriesmenu">
 <?php echo Horde_Util::formInput() ?>
 <select name="category_id" onchange="document.stockcategoriesmenu.submit()">
  <option value=""><?php echo _("Show Category:"); ?></option>
<?php foreach ($this->allCategories() as $category): ?>
  <option value="<?php echo $category->category_id ?>"<?php if (in_array($category->category_id, $this->selectedCategories)) echo ' selected="selected"' ?>><?php echo $category->category ?></option>
<?php endforeach ?>
 </select>
</form>
</div>

<table id="stock" class="stock sortable striped" cellspacing="0">
<thead>
 <tr class="item leftAlign">
  <th width="1%" class="nosort">&nbsp;</th>
<?php foreach ($this->columnHeaders as $header): ?>
  <th id="<?php echo $header['id'] ?>" <?php echo $header['class'] ?> <?php echo $header['width'] ?>><?php echo $header['link'] ?></th>
<?php endforeach ?>
 </tr>
</thead>
<tbody>
<?php foreach ($this->shownStock as $item): ?>
 <tr>
<?php foreach ($item['columns'] as $column): ?>
  <td <?php echo $column['class'] ?>><?php echo $column['column'] ?></td>
<?php endforeach ?>
 </tr>
<?php endforeach ?>
</tbody>
</table>
