<?php
if ($group == $GLOBALS['registry']->getAuth()) {
    $owner_title = _("My Galleries");
    $custom = '';
} elseif (!empty($GLOBALS['conf']['gallery']['customlabel'])) {
    $uprefs = $injector->getInstance('Horde_Core_Factory_Prefs')->create('ansel', array(
        'cache' => false,
        'user' => $group
    ));
    $custom = $uprefs->getValue('grouptitle');
    $identity = new Horde_Prefs_Identity(array('prefs' => $uprefs, 'user' => $group));
    $fullname = $identity->getName();
    if (!$fullname) {
        $fullname = $group;
    }
    if (!$custom) {
        $owner_title = sprintf(_("%s's Galleries"), htmlspecialchars($fullname));
    } else {
        $owner_title = $custom;
    }
} else {
    $owner_title = sprintf(_("%s's Galleries"), $group);
}

$link = Ansel::getUrlFor('view', array('groupby' => $groupby,
                                       'owner' => $group,
                                       'view' => 'List'))->link();

$num_galleries = $GLOBALS['injector']->getInstance('Ansel_Storage')
    ->countGalleries($GLOBALS['registry']->getAuth(), Horde_Perms::SHOW, $group, null, false);

if ($num_galleries < 5) {
    $mini_galleries = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->listGalleries(array('attributes' => $group, 'all_levels' => false));
} else {
    $mini_galleries = $GLOBALS['injector']
        ->getInstance('Ansel_Storage')
        ->listGalleries(array('attributes' => $group,
                              'from' => rand(0, $num_galleries - 4),
                              'count' => 4));
}
$mini_galleries = array_values($mini_galleries);
?>
<td class="galleryGroupsTile" width="<?php echo round(100 / $prefs->getValue('tilesperrow')) ?>%" valign="top">
 <table>
  <tr>
<?php for ($i = 0; $i < count($mini_galleries); ++$i): ?>
   <td align="center">
    <?php if ($mini_galleries[$i]->isOldEnough() && !$mini_galleries[$i]->hasPasswd()): ?>
     <?php echo $link ?><img src="<?php echo Ansel::getImageUrl($mini_galleries[$i]->getKeyImage(Ansel::getStyleDefinition('ansel_default')), 'mini', false) ?>" alt="<?php echo $owner_title?>" /></a>
    <?php else: ?>
      <?php echo $link . Horde::img('gallery-locked-mini.png') ?></a>
    <?php endif; ?>
   </td>

<?php if ($i == 1) echo "  </tr>\n  <tr>\n" ?>
<?php endfor; ?>
  </tr>
 </table>

 <p class="light">
  <strong><?php echo $link . $owner_title ?></a></strong><br /><?php echo ((!empty($custom)) ? sprintf(_("Owned by: %s"), $fullname) . '<br />' : '') ?>
  <?php echo sprintf(ngettext("%d Gallery", "%d Galleries", $num_galleries), $num_galleries) ?>
 </p>
</td>

<?php if (!(++$count % $prefs->getValue('tilesperrow'))) echo "</tr>\n<tr>\n" ?>
