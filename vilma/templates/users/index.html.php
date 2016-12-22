<h1 class="header"><?php echo _("Users") ?></h1>
<?php echo $this->tabs ?>
<?php if ($this->addresses): ?>
<table class="horde-table">
  <tr>
    <th>&nbsp;</th>
    <th><?php echo _("Address") ?></th>
    <th><?php echo _("Full Name") ?></th>
    <th><?php echo _("Type") ?></th>
    <th><?php echo _("Status") ?></th>
  </tr>
<?php foreach ($this->addresses as $address): ?>
  <tr>
    <td>
<?php if ($address['edit_url']): ?>
      <a href="<?php echo $address['edit_url'] ?>"><?php echo $this->images['edit'] ?></a>
<?php endif ?>
      <a href="<?php echo $address['del_url'] ?>"><?php echo $this->images['delete'] ?></a>
<?php if ($address['add_alias_url']): ?>
      <a href="<?php echo $address['add_alias_url'] ?>">+Alias</a>
<?php endif ?>
<?php if ($address['add_forward_url']): ?>
      <a href="<?php echo $address['add_forward_url'] ?>">+Forward</a>
<?php endif ?>
    </td>
    <td>
<?php if ($address['view_url']): ?>
      <a href="<?php echo $address['view_url'] ?>">
<?php endif ?>
<?php if ($address['user_name']): ?>
      <?php echo $address['user_name'] ?>&nbsp;
<?php endif ?>
<?php if ($address['address']): ?>
      &lt;<?php echo $address['address'] ?>&gt;
<?php endif ?>
<?php if ($address['view_url']): ?>
      </a>
<?php endif ?>
    </td>
    <td align="center">
      <?php echo $address['user_full_name'] ?>
    </td>
    <td class="rightAlign">
      <?php echo $address['type'] ?>
    </td>
    <td>
<?php foreach ($address['status'] as $status): ?>
      <?php echo $status ?><br />
<?php endforeach ?>
    </td>
  </tr>
<?php endforeach ?>
</table>
<?php echo $this->pager ?>
<?php else: ?>
<p><em><?php echo _("No users yet") ?></em></p>
<?php endif ?>
