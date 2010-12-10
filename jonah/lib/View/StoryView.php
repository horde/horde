<?php
/**
 * Jonah_View_StoryView:: class to display an individual story.
 *
 * Copyright 2003-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file LICENSE for license information (BSD). If you
 * did not receive this file, see http://cvs.horde.org/co.php/jonah/LICENSE.
 *
 * @author Chuck Hagenbuch <chuck@horde.org>
 * @author Michael J. Rubinsky <mrubinsk@horde.org>
 * @package Jonah
 */
class Jonah_View_StoryView extends Jonah_View_Base
{
    /**
     * Expects
     *   $registry
     *   $notification
     *   $browser
     *   $story_id
     *   $channel_id
     *
     */
    public function run()
    {
        extract($this->_params, EXTR_REFS);

        Horde::addScriptFile('syntaxhighlighter/scripts/shCore.js', 'horde', true);
        Horde::addScriptFile('syntaxhighlighter/scripts/shAutoloader.js', 'horde', true);
        $path = $GLOBALS['registry']->get('jsuri', 'horde') . '/syntaxhighlighter/scripts/';
        $brushes = <<<EOT
          SyntaxHighlighter.autoloader(
          'applescript            {$path}shBrushAppleScript.js',
          'actionscript3 as3      {$path}shBrushAS3.js',
          'bash shell             {$path}shBrushBash.js',
          'coldfusion cf          {$path}shBrushColdFusion.js',
          'cpp c                  {$path}shBrushCpp.js',
          'c# c-sharp csharp      {$path}shBrushCSharp.js',
          'css                    {$path}shBrushCss.js',
          'delphi pascal          {$path}shBrushDelphi.js',
          'diff patch pas         {$path}shBrushDiff.js',
          'erl erlang             {$path}shBrushErlang.js',
          'groovy                 {$path}shBrushGroovy.js',
          'java                   {$path}shBrushJava.js',
          'jfx javafx             {$path}shBrushJavaFX.js',
          'js jscript javascript  {$path}shBrushJScript.js',
          'perl pl                {$path}shBrushPerl.js',
          'php                    {$path}shBrushPhp.js',
          'text plain             {$path}shBrushPlain.js',
          'py python              {$path}shBrushPython.js',
          'ruby rails ror rb      {$path}shBrushRuby.js',
          'sass scss              {$path}shBrushSass.js',
          'scala                  {$path}shBrushScala.js',
          'sql                    {$path}shBrushSql.js',
          'vb vbnet               {$path}shBrushVb.js',
          'xml xhtml xslt html    {$path}shBrushXml.js'
        );
EOT;
        Horde::addInlineScript(array(
            $brushes,
            'SyntaxHighlighter.defaults[\'toolbar\'] = false',
            'SyntaxHighlighter.all()'
        ), 'dom');

        $sh_js_fs = $GLOBALS['registry']->get('jsfs', 'horde') . '/syntaxhighlighter/styles/';
        $sh_js_uri = Horde::url($GLOBALS['registry']->get('jsuri', 'horde'), false, -1) . '/syntaxhighlighter/styles/';

        $css = $GLOBALS['injector']->getInstance('Horde_Themes_Css');
        $css->addStylesheet($sh_js_fs . 'shCoreEclipse.css', $sh_js_uri . 'shCoreEclipse.css');
        $css->addStylesheet($sh_js_fs . 'shThemeEclipse.css', $sh_js_uri . 'shThemeEclipse.css');

        $driver = $GLOBALS['injector']->getInstance('Jonah_Driver');
        try {
            $story = $driver->getStory($channel_id, $story_id, !$browser->isRobot());
        } catch (Exception $e) {
            $notification->push(sprintf(_("Error fetching story: %s"), $e->getMessage()), 'horde.warning');
            require $registry->get('templates', 'horde') . '/common-header.inc';
            require JONAH_TEMPLATES . '/menu.inc';
            require $registry->get('templates', 'horde') . '/common-footer.inc';
            exit;
        }

        /* Grab tag related content for entire channel */
        $cloud = new Horde_Core_Ui_TagCloud();
        $allTags = $driver->listTagInfo(array(), $channel_id);
        foreach ($allTags as $tag_id => $taginfo) {
            $cloud->addElement($taginfo['tag_name'], Horde::url('stories/results.php')->add(array('tag_id' => $tag_id, 'channel_id' => $channel_id)), $taginfo['total']);
        }

        /* Prepare the story's tags for display */
        // FIXME - need to actually use these.
        $tag_html = array();
        $tag_link = Horde::url('stories/results.php')->add('channel_id', $channel_id);
        foreach ($story['tags'] as $id => $tag) {
            $link = $tag_link->copy()->add('tag_id', $id);
            $tag_html[] = $link->link() . $tag . '</a>';
        }

        /* Filter and prepare story content. */
        if (!empty($story['body_type']) && $story['body_type'] == 'text') {
            $story['body'] = $GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter($story['body'], 'text2html', array('parselevel' => Horde_Text_Filter_Text2html::MICRO));
        }

        // @TODO: Where is this used and what for?
        if (!empty($story['url'])) {
            $story['body'] .= Horde::link(Horde::externalUrl($story['url'])) . htmlspecialchars($story['url']) . '</a></p>';
        }

        if (empty($story['published_date'])) {
            $story['published_date'] = false;
        }

        $view = new Horde_View(array('templatePath' => array(JONAH_TEMPLATES . '/stories',
                                                             JONAH_TEMPLATES . '/stories/partial',
                                                             JONAH_TEMPLATES . '/stories/layout')));
        $view->addHelper('Tag');
        $view->addHelper('Text');
        $view->tagcloud = $cloud->buildHTML();
        $view->story = $story;

        /* Insert link for sharing. */
        if ($conf['sharing']['allow']) {
            $url = Horde::url('stories/share.php')->add(array('id' => $story['id'], 'channel_id' => $channel_id));
            $view->sharelink = $url->link() . _("Share this story") . '</a>';
        }

        /* Insert comments. */
        if ($conf['comments']['allow']) {
            if (!$registry->hasMethod('forums/doComments')) {
                $err = 'User comments are enabled but the forums API is not available.';
                Horde::logMessage($err, 'ERR');
            } else {
                try {
                    $comments = $registry->call('forums/doComments', array('jonah', $story_id, 'commentCallback'));
                } catch (Exception $e) {
                    Horde::logMessage($e, 'ERR');
                    $comments = array('threads' => '', 'comments' => '');
                }
                $view->comments = $comments;
            }
        }

        require $registry->get('templates', 'horde') . '/common-header.inc';
        require JONAH_TEMPLATES . '/menu.inc';
        echo $view->render('view');
        require $registry->get('templates', 'horde') . '/common-footer.inc';
    }

}
