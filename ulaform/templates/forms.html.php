<?php if (!empty($this->forms)): ?>
<h1 class="header">
 <?php echo $this->header; ?>
</h1>

<table width="100%" class="linedRow" cellspacing="0">
  <tr>
    <th class="item nowrap" width="1%">&nbsp;</th>
    <?php foreach ($this->listheaders as $listheader): ?>
    <th class="item leftAlign">
      <?php echo $listheader; ?>
    </th>
    <?php endforeach; ?>
  </tr>
  <?php foreach ($this->forms as $form): ?>
  <tr>
    <td class="nowrap">
      <a href="<?php echo $form['edit_url']; ?>"><?php echo $this->images['edit']; ?></a>
      <a href="<?php echo $form['del_url']; ?>"><?php echo $this->images['delete']; ?></a>
      <a href="<?php echo $form['preview_url']; ?>" target="_blank"><?php echo $this->images['preview']; ?></a>
      <a href="<?php echo $form['html_url']; ?>"><?php echo $this->images['html']; ?></a>
    </td>
    <td>
      <a href="<?php echo $form['view_url']; ?>"><?php echo $form['name']; ?></a>
    </td>
    <td>
      <?php echo $form['action']; ?>
    </td>
  </tr>
  <?php endforeach; ?>
</table>
<?php endif; ?>
