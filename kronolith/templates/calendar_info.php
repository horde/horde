<?php if (is_array($calendar)): ?>

<h2><?php echo htmlspecialchars($calendar['name']) ?></h2>
<p>
 <?php echo _("Remote Calendar from: ") ?>
</p>
<p class="calendar-info-url">
 <?php echo htmlspecialchars($calendar['url']) ?>
</p>

<?php else: ?>

<h2><?php echo htmlspecialchars($calendar->name()) ?></h2>
<?php if ($desc = $calendar->description()): ?>
<p><em><?php echo htmlspecialchars($desc) ?></em></p>
<?php endif; ?>
<p>
 <?php echo $calendar->owner() ? sprintf(_("Local calendar owned by %s."), Kronolith::getUserName($calendar->owner())) : _("System calendar.") ?>
 <?php echo _("To subscribe to this calendar from another calendar program, use this URL:") ?>
</p>
<p class="calendar-info-url">
 <?php echo htmlspecialchars($subscribe_url) ?>
</p>
<p>
 <?php echo _("To subscribe to this calendar from a feed reader, use this URL:") ?>
</p>
<p class="calendar-info-url">
 <?php echo htmlspecialchars(Kronolith::feedUrl($calendar->share()->getName())) ?>
</p>

<p>
 <?php echo _("To embed this calendar in another website, use this code:") ?>
</p>
<p class="calendar-info-url">
 <?php echo htmlspecialchars(Kronolith::embedCode($calendar->share()->getName())); ?>
</p>
<?php if (Horde_Menu::showService('help')) {
    echo '<p>' . Horde_Help::link('kronolith', 'embed') . ' ' . _("Learn how to embed other calendar views.") . '</p>';
} ?>
<?php endif; ?>
