<?php
/**
 * This file stores the templates used to generate different views of
 * news channels.
 *
 * IMPORTANT: Local overrides should be placed in templates.local.php, or
 * templates-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */

$templates['standard'] = array('name' => _("Standard"),
                               'template' => '<table width="100%" cellspacing="0" class="linedRow">
<if:error><tr class="text"><td><em><tag:error /></em></td></tr></if:error>
<if:stories>
<loop:stories>
<tr><td><strong><a target="_blank" href="<tag:stories.permalink />"><tag:story_marker /> <tag:stories.title /></a></strong><div style="padding-left:10px"><tag:stories.desc /></div></td></tr>
</loop:stories>
</if:stories>

<if:form><tr><td><tag:form /></td></tr></if:form></table>');

$templates['media'] = array('name' => _("Media"),
                               'template' => '<table width="100%" cellspacing="0" class="linedRow">
<if:error><tr class="text"><td><em><tag:error /></em></td></tr></if:error>
<if:stories>
<loop:stories>
<tr><td><strong><a target="_blank" href="<tag:stories.permalink />"><img title="<tag:stories.media_description />" style="margin:7px;" src="<tag:stories.media_thumbnail_url />" /></a></strong><div style="margin:7px;"><a target="_blank" href="<tag:stories.permalink />"><tag:stories.media_title /></a></div></td></tr>
</loop:stories>
</if:stories>

<if:form><tr><td><tag:form /></td></tr></if:form></table>');

$templates['internal'] = array('name' => _("Internal"),
                               'template' => '<table width="100%" cellspacing="0" class="linedRow">
<if:error><tr class="text"><td><em><tag:error /></em></td></tr></if:error>
<if:stories>
<loop:stories>
<tr><td><strong><a href="<tag:stories.permalink />"><tag:story_marker /> <tag:stories.title /></a></strong><div style="padding-left:10px"><tag:stories.desc /></div></td></tr>
</loop:stories>
</if:stories>

<if:form><tr><td><tag:form /></td></tr></if:form></table>');

$templates['compact'] = array('name' => _("Compact"),
                              'template' => '<table width="100%" cellspacing="1" class="linedRow">
<if:error><tr class="text"><td><em><tag:error /></em></td></tr></if:error>
<if:stories>
<loop:stories>
<tr class="text"><td><a target="_blank" href="<tag:stories.permalink />"><tag:story_marker /> <tag:stories.title /></a></td></tr>
</loop:stories>
</if:stories>

<if:form><tr><td><tag:form /></td></tr></if:form></table>');

$templates['ultracompact'] = array('name' => _("Ultracompact"),
                                   'template' => '<if:error><em><tag:error /></em></if:error>
<if:stories>
<loop:stories>
:: <a target="blank" href="<tag:stories.permalink />"><tag:stories.title /></a>
</loop:stories>
</if:stories>');

/* Local overrides. */
if (file_exists(dirname(__FILE__) . '/templates.local.php')) {
    include dirname(__FILE__) . '/templates.local.php';
}
