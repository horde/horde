 <tr>
  <td class="nowrap"><?php echo $resource['deleteLink'] ?></td>
  <td><?php echo $this->h($resource['name']) ?></td>
  <td>
   <label for="resourceattendance_<?php echo $resourceCounter ?>" class="hidden"><?php echo _("Attendance") ?></label>
   <select id="resourceattendance_<?php echo $resourceCounter ?>" name="resourceattendance_<?php echo $resourceCounter ?>" onchange="performAction('changeResourceAtt', document.attendeesForm.resourceattendance_<?php echo $resourceCounter ?>.value + ' ' + decodeURIComponent('<?php echo rawurlencode($resource['id']) ?>'));">
<?php foreach ($resource['roles'] as $role => $info): ?>
     <option value="<?php echo $role ?>"<?php if ($info['selected']) echo ' selected="selected"' ?>><?php echo $info['label'] ?></option>
<?php endforeach ?>
   </select>
  </td>
  <td>
   <select name="resourceresponse_<?php echo $resourceCounter ?>" onchange="performAction('changeResourceResp', document.attendeesForm.resourceresponse_<?php echo $resourceCounter ?>.value + ' ' + decodeURIComponent('<?php echo rawurlencode($resource['id']) ?>'));">
<?php foreach ($resource['responses'] as $response => $info): ?>
    <option value="<?php echo $response ?>"<?php if ($info['selected']) echo ' selected="selected"' ?>><?php echo $info['label'] ?></option>
<?php endforeach ?>
   </select>
  </td>
 </tr>
