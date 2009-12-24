<tr>
 <td class="nowrap">
  <?php echo Horde::link($page->pageUrl(), sprintf(_("Display %s"), $page->pageName())) . $page->pageName() . '</a>'; ?>
 </td>
 <td class="nowrap">
  <?php echo Horde::link($page->pageUrl(), sprintf(_("Display Version %s"), $page->version())) . $page->version() . '</a>'; ?>
 </td>
 <td class="nowrap"><?php echo $page->author() ?></td>
 <td class="nowrap"><?php echo $page->formatVersionCreated() ?></td>
 <?php if (!empty($hits)): ?>
 <td class="nowrap"><?php echo $page->hits() ?></td>
 <?php endif; ?>
</tr>
