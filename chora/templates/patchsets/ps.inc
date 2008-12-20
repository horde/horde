<tr id="ps<?php echo $id ?>">
 <td class="pset"><?php echo $patchset_link ?></td>
 <td class="ago" sortval="<?php echo (int)$patchset['date'] ?>"><a title="<?php echo $readableDate ?>"><?php echo $commitDate ?></a></td>
 <td class="author"><?php echo $author ?></td>
 <td class="files">
  <ul>
   <?php foreach ($files as $file): ?>
   <li><?php echo $file['file'] . ': ' . $file['from'] . ' -> ' . $file['to'] . ' ' . $file['diff'] ?>
   <?php endforeach; ?>
  </ul>
 </td>
 <td class="log">
<?php
echo '<p class="message">' . $logMessage . '</p>';
$tags = array();
if (isset($patchset['branch'])) {
    $tags[] = htmlspecialchars($patchset['branch']);
}
if (isset($patchset['tag'])) {
    $tags[] = htmlspecialchars($patchset['tag']);
}
if ($tags) echo '<p class="tags">' . _("Tags") . ': ' . implode(', ', $tags) . '</p>';
?>
 </td>
</tr>
