<h1 class="header"><?php echo _("Domains") ?></h1>
<?php if ($this->domains): ?>
<table class="horde-table">
  <tr>
    <th>&nbsp;</th>
    <th><?php echo _("Domain") ?></th>
    <th><?php echo _("Max Users") ?></th>
  </tr>
<?php foreach ($this->domains as $domain): ?>
  <tr>
    <td>
      <a href="<?php echo $domain['edit_url'] ?>"><?php echo $this->images['edit'] ?></a>
      <a href="<?php echo $domain['del_url'] ?>"><?php echo $this->images['delete'] ?></a>
    </td>
    <td>
      <a href="<?php echo $domain['view_url'] ?>"><?php echo $this->h($domain['domain_name']) ?></a>
    </td>
    <td class="rightAlign">
      <?php echo $domain['domain_max_users'] ?>
    </td>
  </tr>
<?php endforeach ?>
</table>
<?php else: ?>
<p><em><?php echo _("No domains yet") ?></em></p>
<?php endif ?>
