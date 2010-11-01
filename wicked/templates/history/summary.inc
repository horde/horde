<tr>
 <td><?php echo Horde::link($page->pageUrl(), sprintf(_("Display Version %s"), $page->version())) . htmlspecialchars($page->version()) ?></a></td>
<?php if ($this->allows(Wicked::MODE_REMOVE)): ?>
 <td style="text-align:center">
<?php
$text = sprintf(_("Delete Version %s"), $page->version());
$url = Horde_Util::addParameter(Wicked::url('DeletePage'), array('referrer' => $page->pageName(),
                                                           'version' => $page->version()));
echo Horde::link($url, $text) . Horde::img('delete.png', $text) . '</a>';
?>
 </td>
<?php endif; if ($this->allows(Wicked::MODE_EDIT)): ?>
 <td style="text-align:center">
<?php
if ($show_edit) {
    $text = sprintf(_("Edit Version %s"), $page->version());
    $url = Horde_Util::addParameter(Wicked::url('EditPage'), array('referrer' => $page->pageName()));
    echo Horde::link($url, $text) . Horde::img('edit.png', $text) . '</a>';
}
?>
 </td>

 <td style="text-align:center">
<?php
if ($show_restore) {
    $text = sprintf(_("Revert to version %s"), $page->version());
    $url = Horde_Util::addParameter(Wicked::url('RevertPage'), array('referrer' => $page->pageName(),
                                                               'version' => $page->version()));
    echo Horde::link($url, $text) . Horde::img('restore.png', $text) . '</a>';
}
?>
</td>
<?php endif; ?>

 <td class="nowrap"><?php echo htmlspecialchars($page->author()) ?></td>
 <td class="nowrap"><?php echo htmlspecialchars($page->formatVersionCreated()) ?></td>

<?php if ($allow_diff): $pversion = $page->isOld() ? $page->version() : ''; ?>
 <td style="text-align:center"><input type="radio" name="v1" value="<?php echo htmlspecialchars($pversion) ?>" /></td>
 <td style="text-align:center"><input type="submit" class="button" value="<?php echo htmlspecialchars($page->version()) ?>" onclick="if (document.diff.v1[<?php echo $i ?>].checked) return false; document.diff.v2.value = '<?php echo htmlspecialchars($pversion) ?>';" /></td>
<?php endif; ?>

 <td><?php echo htmlspecialchars($page->changeLog()) ?></td>
</tr>
