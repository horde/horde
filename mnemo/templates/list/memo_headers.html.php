<table id="memos" class="horde-table sortable nowrap">
<thead>
 <tr>
  <th class="nosort" width="3%"><?php echo $this->editImg ?></th>
<?php foreach ($this->headers as $header): ?>
  <th id="<?php echo $header['id'] ?>" class="horde-split-left <?php if ($header['sorted']) echo ' ' . $this->sortdirclass ?>" width="<?php echo $header['width'] ?>">
   <?php echo $header['label'] ?>
  </th>
<?php endforeach ?>
 </tr>
</thead>
<tbody id="notes_body">
