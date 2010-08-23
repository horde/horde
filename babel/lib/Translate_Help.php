<?php
/**
 * Copyright 2009-2010 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (GPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/gpl.html.
 *
 * @author  Joel Vandal <joel@scopserv.com>
 * @package Babel
 */

class Translate_Help {
    function update_help()
    {
	global $dirs, $apps, $last_error_msg, $lang, $module;
	
	$files = array();
	for ($i = 0; $i < count($dirs); $i++) {
	    if (!empty($module) && $module != $apps[$i]) { continue; }
	    if (!is_dir("$dirs[$i]/locale")) continue;
	    if ($apps[$i] == 'horde') {
		$dirs[] = $dirs[$i] . '/' . 'admin';
		$apps[] = 'horde/admin';
		if (!empty($module)) {
		    $module = 'horde/admin';
		}
	    }
	    if (empty($lang)) {
		$files = search_file('help.xml', $dirs[$i] . '/' . 'locale');
	    } else {
		$files = array($dirs[$i] . '/' . 'locale' . '/' . $lang . '/' . 'help.xml');
	    }
	    $file_en  = $dirs[$i] . '/' . 'locale' . '/' . 'en' . '/' . 'help.xml';
	    if (!@file_exists($file_en)) {
		Translate_Display::info(sprintf(_("Warning: There doesn't yet exist a help file for %s."), $apps[$i]));
		Translate_Display::info();
		continue;
	    }
	    foreach ($files as $file_loc) {
		$locale = substr($file_loc, 0, strrpos($file_loc, '/'));
		$locale = substr($locale, strrpos($locale, '/') + 1);
		if ($locale == 'en') continue;
		if (!@file_exists($file_loc)) {
		    Translate_Display::info(sprintf(_("Warning: The %s help file for %s doesn't yet exist. Creating a new one."), $locale, $apps[$i]));
		    $dir_loc = substr($file_loc, 0, -9);
		    if (!is_dir($dir_loc)) {
			require_once 'System.php';
			if (!@System::mkdir("-p $dir_loc")) {
			    Translate_Display::error(sprintf(_("Warning: Could not create locale directory for locale %s:"), $locale));
			    Translate_Display::info($dir_loc, false);
			    Translate_Display::info();
			    continue;
			}
		    }
		    
		    if (!@copy($file_en, $file_loc)) {
			Translate_Display::error(sprintf(_("Warning: Could not copy %s to %s"), $file_en, $file_loc));
		    }
		    Translate_Display::info();
		    continue;
		}
		Translate_Display::info(sprintf(_("Updating %s help file for %s."), $locale, $apps[$i]));
		$fp = fopen($file_loc, 'r');
		$line = fgets($fp);
		fclose($fp);
		if (!strstr($line, '<?xml')) {
		    Translate_Display::info(sprintf(_("Warning: The help file %s didn't start with &lt;?xml"), $file_loc));
		    Translate_Display::info();
		    continue;
		}
		$encoding = '';
		if (preg_match('/encoding=(["\'])([^\\1]+)\\1/', $line, $match)) {
		    $encoding = $match[2];
		}
		$doc_en = domxml_open_file($file_en);
		if (!is_object($doc_en)) {
		    Translate_Display::info(sprintf(_("Warning: There was an error opening the file %s."), $file_en));
		    Translate_Display::info();
		    continue 2;
		}
		$doc_loc = domxml_open_file($file_loc);
		if (!is_object($doc_loc)) {
		    Translate_Display::info(sprintf(_("There was an error opening the file %s."), $file_loc));
		    Translate_Display::info();
		    continue;
		}
		$doc_new  = domxml_new_doc('1.0');
		$help_en  = $doc_en->document_element();
		$help_loc = $doc_loc->document_element();
		$help_new = $help_loc->clone_node();
		$entries_loc = array();
		$entries_new = array();
		$count_uptodate = 0;
		$count_new      = 0;
		$count_changed  = 0;
		$count_unknown  = 0;
		foreach ($doc_loc->get_elements_by_tagname('entry') as $entry) {
		    $entries_loc[$entry->get_attribute('id')] = $entry;
		}
		foreach ($doc_en->get_elements_by_tagname('entry') as $entry) {
		    $id = $entry->get_attribute('id');
		    if (array_key_exists($id, $entries_loc)) {
			if ($entries_loc[$id]->has_attribute('md5') &&
			    md5($entry->get_content()) != $entries_loc[$id]->get_attribute('md5')) {
			    $comment = $doc_loc->create_comment(" English entry:\n" . str_replace('--', '&#45;&#45;', $doc_loc->dump_node($entry)));
			    $entries_loc[$id]->append_child($comment);
			    $entry_new = $entries_loc[$id]->clone_node(true);
			    $entry_new->set_attribute('state', 'changed');
			    $count_changed++;
			} else {
			    if (!$entries_loc[$id]->has_attribute('state')) {
				$comment = $doc_loc->create_comment(" English entry:\n" . str_replace('--', '&#45;&#45;', $doc_loc->dump_node($entry)));
				$entries_loc[$id]->append_child($comment);
				$entry_new = $entries_loc[$id]->clone_node(true);
				$entry_new->set_attribute('state', 'unknown');
				$count_unknown++;
			    } else {
				$entry_new = $entries_loc[$id]->clone_node(true);
				$count_uptodate++;
			    }
			}
		    } else {
			$entry_new = $entry->clone_node(true);
			$entry_new->set_attribute('state', 'new');
			$count_new++;
		    }
		    $entries_new[] = $entry_new;
		}
		$doc_new->append_child($doc_new->create_comment(' $' . 'Horde$ '));
		foreach ($entries_new as $entry) {
		    $help_new->append_child($entry);
		}
		Translate_Display::info(sprintf(_("Entries: %d total, %d up-to-date, %d new, %d changed, %d unknown"),
						$count_uptodate + $count_new + $count_changed + $count_unknown,
						$count_uptodate, $count_new, $count_changed, $count_unknown), false);
		$doc_new->append_child($help_new);
		$output = $doc_new->dump_mem(true, $encoding);
		$fp = fopen($file_loc, 'w');
		$line = fwrite($fp, $output);
		fclose($fp);
		Translate_Display::info(sprintf(_("%d bytes written."), strlen($output)), false);
		Translate_Display::info();
	    }
	}
    }
    
    function make_help()
    {
	global $dirs, $apps, $lang, $module;
	
	$files = array();
	for ($i = 0; $i < count($dirs); $i++) {
	    if (!empty($module) && $module != $apps[$i]) continue;
	    if (!is_dir("$dirs[$i]/locale")) continue;
	    if ($apps[$i] == 'horde') {
		$dirs[] = $dirs[$i] . '/' . 'admin';
		$apps[] = 'horde/admin';
		if (!empty($module)) {
		    $module = 'horde/admin';
		}
	    }
	    if (empty($lang)) {
		$files = search_file('help.xml', $dirs[$i] . '/' . 'locale');
	    } else {
		$files = array($dirs[$i] . '/' . 'locale' . '/' . $lang . '/' . 'help.xml');
	    }
	    $file_en  = $dirs[$i] . '/' . 'locale' . '/' . 'en' . '/' . 'help.xml';
	    if (!@file_exists($file_en)) {
		continue;
	    }
	    foreach ($files as $file_loc) {
		if (!@file_exists($file_loc)) {
		    Translate_Display::info(_("Skipped..."));
		    Translate_Display::info();
		    continue;
		}
		$locale = substr($file_loc, 0, strrpos($file_loc, '/'));
		$locale = substr($locale, strrpos($locale, '/') + 1);
		if ($locale == 'en') continue;
		Translate_Display::info(sprintf(_("Updating %s help file for %s."), ($locale), ($apps[$i])));
		$fp = fopen($file_loc, 'r');
		$line = fgets($fp);
		fclose($fp);
		if (!strstr($line, '<?xml')) {
		    Translate_Display::info(sprintf(_("Warning: The help file %s didn't start with &lt;?xml"), $file_loc));
		    Translate_Display::info();
		    continue;
		}
		$encoding = '';
		if (preg_match('/encoding=(["\'])([^\\1]+)\\1/', $line, $match)) {
		    $encoding = $match[2];
		}
		$doc_en   = domxml_open_file($file_en);
		if (!is_object($doc_en)) {
		    Translate_Display::info(sprintf(_("Warning: There was an error opening the file %s."), $file_en));
		    Translate_Display::info();
		    continue 2;
		}
		$doc_loc  = domxml_open_file($file_loc);
		if (!is_object($doc_loc)) {
		    Translate_Display::info(sprintf(_("Warning: There was an error opening the file %s."), $file_loc));
		    Translate_Display::info();
		    continue;
		}
		$help_loc = $doc_loc->document_element();
		$md5_en   = array();
		$count_all = 0;
		$count     = 0;
		foreach ($doc_en->get_elements_by_tagname('entry') as $entry) {
		    $md5_en[$entry->get_attribute('id')] = md5($entry->get_content());
		}
		foreach ($doc_loc->get_elements_by_tagname('entry') as $entry) {
		    foreach ($entry->child_nodes() as $child) {
			if ($child->node_type() == XML_COMMENT_NODE && strstr($child->node_value(), 'English entry')) {
			    $entry->remove_child($child);
			}
		    }
		    $count_all++;
		    $id = $entry->get_attribute('id');
		    if (!array_key_exists($id, $md5_en)) {
			Translate_Display::info(sprintf(_("No entry with the id '%s' exists in the original help file."), $id));
		    } else {
			$entry->set_attribute('md5', $md5_en[$id]);
			$entry->set_attribute('state', 'uptodate');
			$count++;
		    }
		}
		$output = $doc_loc->dump_mem(true, $encoding);
		$fp = fopen($file_loc, 'w');
		$line = fwrite($fp, $output);
		fclose($fp);
		
		Translate_Display::info(sprintf(_("%d of %d entries marked as up-to-date"), $count, $count_all), false);
		Translate_Display::info();
	    }
	}
    }
}
