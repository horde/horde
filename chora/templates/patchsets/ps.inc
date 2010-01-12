<tr id="ps<?php echo $id ?>">
 <td class="pset"><?php echo $patchset_link ?></td>
 <td class="ago" sortval="<?php echo (int)$patchset['date'] ?>"><a title="<?php echo $readableDate ?>"><?php echo $commitDate ?></a></td>
 <td class="author"><?php echo $author ?></td>
 <td class="files">
  <ul>
<?php foreach ($files as $file): ?>
   <li><?php echo $file['file'] . ': ' . $file['from'] . ' -> ' . $file['to'] . ' ' . $file['diff'] ?></li>
<?php endforeach; ?>
  </ul>
 </td>
 <td class="log">
  <p class="message"><?php echo $logMessage ?></p>
<?php if (!empty($tags)): ?>
  <p class="tags"><?php echo _("Tags") . ': ' . implode(', ', array_map('htmlspecialchars', $tags)) ?></p>
<?php endif; ?>
 </td>
</tr>
