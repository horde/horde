<br />
<table class="horde-table" width="100%">
<?php foreach ($this->changes as $change): ?>
 <thead>
  <tr>
   <th colspan="4"><?php echo $change['date'] ?></th>
  </tr>
 </thead>
 <tbody>
<?php foreach ($change['pages'] as $page): ?>
  <tr>
   <td class="nowrap" width="20%"><a href="<?php echo $page['url'] ?>"><?php echo $page['name'] ?></a></td>
   <td class="nowrap" width="5%"><a href="<?php echo $page['version_url'] ?>" title="<?php echo $page['version_alt'] ?>"><?php echo $page['version'] ?></a> <a href="<?php echo $page['diff_url'] ?>" title="<?php echo $page['diff_alt'] ?>"><?php echo $page['diff_img'] ?></a></td>
   <td class="nowrap" width="15%"><?php echo $page['author'] ?></td>
   <td width="58%"><?php echo $page['change_log'] ?></td>
  </tr>
<?php endforeach ?>
 </tbody>
<?php endforeach ?>
</table>
