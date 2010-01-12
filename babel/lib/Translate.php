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

class Translate {

    function getPath($app) {
	if ($app == 'horde') {
	    $app = '';
	}
	return realpath(HORDE_BASE . '/' . $app . '/po/');
    }
    
    function stats($app, $filter_lang = false) {
	global $module, $apps, $dirs, $lang;
	
	$report = array();
	$dir = Translate::getPath($app);
	
	$i = 0;
	$handle = opendir($dir);
	while ($file = readdir($handle)) {
	    if (preg_match('/(.*)\.po$/', $file, $matches)) {
		$locale = $matches[1];
		if ($filter_lang && $locale != $filter_lang) {
		    continue;
		}
		
		if (!isset(Horde_Nls::$config['languages'][$locale]) || $locale == 'en_US') {
		    continue; 
		}
		$i++;
		
		$pofile = $dir . "/$file";
		
		$tmppo = new File_Gettext_PO();
		$tmppo->load($pofile);
		$fuzzy = 0;
		$untranslated = 0;
		$translated = 0;
		$obsolete = 0;
		
		foreach($tmppo->status as $msgid => $status) {
		    if (in_array('untranslated', $status)) {
			$untranslated++;
		    } elseif (in_array('fuzzy', $status)) {
			$fuzzy++;
		    } elseif (in_array('obsolete', $status)) {
			$obsolete++;
		    } else {
			$translated++;
		    }
		}
		
		$all = $translated + $fuzzy + $untranslated;
		$percent_done = round($translated / $all * 100, 2);
		$report[$locale] = array($all, $percent_done, $translated, $fuzzy, $untranslated, $obsolete);
	    }
	}
	uasort ($report, 'my_usort_function');
	
	return $report;
    }

    function sanity_check()
    {  
	
	/* Sanity checks */
	if (!extension_loaded('gettext')) {
	    Translate_Display::error(_("Gettext extension not found!"));
	}
	
	Translate_Display::info(_("Loading libraries..."));
	$libs_found = true;
	
	foreach (array('Console_Getopt' => 'Console/Getopt.php',
		       'Console_Table'  => 'Console/Table.php',
		       'File_Find'      => 'File/Find.php')
		 as $class => $file) {
	    @include_once $file;
	    if (class_exists($class)) {
		// Translate_Display::info("$class ...", false);
	    } else {
		Translate_Display::error(sprintf(_("%s not found."), $class));
		$libs_found = false;
	    }
	}
	
	if (!$libs_found) {
	    Translate_Display::info();
	    Translate_Display::info(_("Make sure that you have PEAR installed and in your include path."));
	    Translate_Display::info('include_path: ' . ini_get('include_path'));
	}
    }
    
    function check_binaries()
    {
	global $gettext_version, $c;
	
	Translate_Display::info(_("Searching gettext binaries..."));
	require_once 'System.php';
	foreach (array('gettext', 'msgattrib', 'msgcat', 'msgcomm', 'msgfmt', 'msginit', 'msgmerge', 'xgettext') as $binary) {
	    $GLOBALS[$binary] = System::which($binary);
	    if ($GLOBALS[$binary]) {
		// Translate_Display::info("$binary ... found: " . $GLOBALS[$binary], false);
	    } else {
		Translate_Display::error(sprintf(_("%s not found."), $binary));
	    }
	}
	
	$out = '';
	exec($GLOBALS['gettext'] . ' --version', $out, $ret);
	$split = explode(' ', $out[0]);
	// Translate_Display::info('gettext version: ' . $split[count($split) - 1]);
	$gettext_version = explode('.', $split[count($split) - 1]);
	if ($gettext_version[0] == 0 && $gettext_version[1] < 12) {
	    $GLOBALS['php_support'] = false;
	    Translate_Display::info();
	    Translate_Display::warning(_("Warning: Your gettext version is too old and does not support PHP natively."));
	    Translate_Display::warning(_("Not all strings will be extracted."), false);
	} else {
	    $GLOBALS['php_support'] = true;
	}
	Translate_Display::info();
    }
    
    function search_file($file, $dir = '.', $local = false)
    {
	static $ff;
	if (!isset($ff)) {
	    $ff = new File_Find();
	}
	
	if (substr($file, 0, 1) != '/') {
	    $file = "/$file/";
	}
	
	if ($local) {
	    $files = $ff->glob($file, $dir, 'perl');
	    $files = array_map(create_function('$file', 'return "' . $dir . '/' . '" . $file;'), $files);
	    return $files;
	} else {
	    return $ff->search($file, $dir, 'perl');
	}
    }
    
    function search_ext($ext, $dir = '.', $local = false)
    {
	return Translate::search_file(".+\\.$ext\$", $dir, $local);
    }
    
    function get_po_files($dir)
    {
	$langs = Translate::search_ext('po', $dir);
	if (($key = array_search($dir . '/' . 'messages.po', $langs)) !== false) {
	    unset($langs[$key]);
	}
	if (($key = array_search($dir . '/' . 'compendium.po', $langs)) !== false) {
	    unset($langs[$key]);
	}
	return $langs;
    }
    
    function get_languages($dir)
    {
	chdir($dir);
	$langs = get_po_files('po');
	$langs = array_map(create_function('$lang', 'return str_replace("po" . '/', "", str_replace(".po", "", $lang));'), $langs);
	return $langs;
    }
    
    function search_applications()
    {
	$dirs = array();
	$horde = false;
	if (@is_dir(HORDE_BASE . '/' . 'po')) {
	    $dirs[] = HORDE_BASE;
	    $horde = true;
	}
	$dh = @opendir(HORDE_BASE);
	if ($dh) {
	    while ($entry = @readdir($dh)) {
		$dir = HORDE_BASE . '/' . $entry;
		if (is_dir($dir) &&
		    substr($entry, 0, 1) != '.' &&
		    fileinode(HORDE_BASE) != fileinode($dir)) {
		    $sub = opendir($dir);
		    if ($sub) {
			while ($subentry = readdir($sub)) {
			    if ($subentry == 'po' && is_dir($dir . '/' . $subentry)) {
				$dirs[] = $dir;
				if ($entry == 'horde') {
				    $horde = true;
				}
				break;
			    }
			}
		    }
		}
	    }
	    if (!$horde) {
		array_unshift($dirs, HORDE_BASE);
	    }
	}
	
	return $dirs;
    }
    
    function strip_horde($file)
    {
	if (is_array($file)) {
	    return array_map(create_function('$file', 'return Translate::strip_horde($file);'), $file);
	} else {
	    return str_replace(HORDE_BASE . '/', '', $file);
	}
    }

    function commit($help_only = false)
    {
	global $apps, $dirs, $lang, $module;
	
	$docs = false;
	$files = array();
	for ($i = 0; $i < count($dirs); $i++) {
	    if (!empty($module) && $module != $apps[$i]) continue;
	    if ($apps[$i] == 'horde') {
		$dirs[] = $dirs[$i] . '/' . 'admin';
		$apps[] = 'horde/admin';
		if (!empty($module)) {
		    $module = 'horde/admin';
		}
	    }
	    if (empty($lang)) {
		if ($help_only) {
		    $files = array_merge($files, Translate::strip_horde(Translate::search_ext('xml', $dirs[$i] . '/' . 'locale')));
		} else {
		    $files = array_merge($files, Translate::strip_horde(Translate::get_po_files($dirs[$i] . '/' . 'po')));
		    $files = array_merge($files, Translate::strip_horde(Translate::search_file('^[a-z]{2}_[A-Z]{2}', $dirs[$i] . '/' . 'locale', true)));
		}
	    } else {
		if ($help_only) {
		    if (!@file_exists($dirs[$i] . '/' . 'locale' . '/' . $lang . '/' . 'help.xml')) continue;
		} else {
		    if (!@file_exists($dirs[$i] . '/po/' . $lang . '.po')) continue;
		    $files[] = Translate::strip_horde($dirs[$i] . '/' . 'po' . '/' . $lang . '.po');
		}
		$files[] = Translate::strip_horde($dirs[$i] . '/' . 'locale' . '/' . $lang);
	    }
	    if ($docs && !$help_only && $apps[$i]) {
		$files[] = Translate::strip_horde($dirs[$i] . '/' . 'docs');
		if ($apps[$i] == 'horde') {
		    $horde_conf = $dirs[array_search('horde', $dirs)] . '/' . 'config' . '/';
		    $files[] = Translate::strip_horde($horde_conf . 'nls.php.dist');
		}
	    }
	}
	chdir(HORDE_BASE);
	if (count($files)) {
	    if ($docs) {
		Translate_Display::info(_("Adding new files to repository:"));
		$sh = 'cvs add';
		foreach ($files as $file) {
		    if (strstr($file, 'locale') || strstr($file, '.po')) {
			$sh .= " $file";
			Translate_Display::info($file, false);
		    }
		}
		$sh .= '; cvs add';
		foreach ($files as $file) {
		    if (strstr($file, 'locale')) {
			if ($help_only) {
			    $sh .= ' ' . $file . '/' . '*.xml';
			    Translate_Display::info($file . '/' . '*.xml', false);
			} else {
			    $sh .= ' ' . $file . '/' . '*.xml ' . $file . '/' . 'LC_MESSAGES';
			    Translate_Display::info($file . '/' . "*.xml\n$file" . '/' . 'LC_MESSAGES', false);
			}
		    }
		}
		if (!$help_only) {
		    $sh .= '; cvs add';
		    foreach ($files as $file) {
			if (strstr($file, 'locale')) {
			    $add = $file . '/' . 'LC_MESSAGES' . '/' . '*.mo';
			    $sh .= ' ' . $add;
			    Translate_Display::info($add, false);
			}
		    }
		}
		Translate_Display::info();
		system($sh);
		Translate_Display::info();
	    }
	    Translate_Display::header(_("Committing:"));
	    Translate_Display::info(implode(' ', $files), false);
	    if (!empty($lang)) {
		$lang = ' ' . $lang;
	    }
	    if (empty($msg)) {
		if ($docs) {
		    $msg = "Add $lang translation.";
		} elseif ($help_only) {
		    $msg = "Update $lang help file.";
		} else {
		    $msg = "Update $lang translation.";
		}
	    }
	    $sh = 'cvs commit -m "' . $msg . '" ' . implode(' ', $files);
	    system($sh);
	}
    }
    
    function xtract()
    {
	global $module, $apps, $dirs, $gettext_version;
	
	require_once 'Horde/Array.php';
	if ($GLOBALS['php_support']) {
	    $language = 'PHP';
	} else {
	    $language = 'C++';
	}
	for ($i = 0; $i < count($dirs); $i++) {
	    if (!empty($module) && $module != $apps[$i]) {
		continue;
	    }
	    Translate_Display::header(sprintf(_("Extracting from %s... "), $apps[$i]));
	    chdir($dirs[$i]);
	    if ($apps[$i] == 'horde') {
		$files = Translate::search_ext('php', '.', true);
		foreach (array('admin', 'framework', 'lib', 'services', 'templates', 'util', 'config' . '/' . 'themes') as $search_dir) {
		    $files = array_merge($files, Translate::search_ext('(php|inc|js)', $search_dir));
		}
		$files = array_merge($files, Translate::search_ext('(php|dist)', 'config'));
		$sh = $GLOBALS['xgettext'] . ' --language=' . $language .
                  ' --from-code=iso-8859-1 --keyword=_ --sort-output --copyright-holder="Horde Project"';
		if ($gettext_version[0] > 0 || $gettext_version[1] > 11) {
		    $sh .= ' --msgid-bugs-address="dev@lists.horde.org"';
		}
		$file = $dirs[$i] . '/' . 'po' . '/' . $apps[$i] . '.pot';
		if (file_exists($file) && !is_writable($file)) {
		    Translate_Display::error(sprintf(_('%s is not writable.', $file)));
		}
		$tmp_file = $file . '.tmp.pot';
		$sh .= ' -o ' . $tmp_file . ' ' . implode(' ', $files);
		if (@file_exists($dirs[$i] . '/po/translation.php')) {
		    $sh .= ' po/translation.php';
		}
		exec($sh);
	    } else {
		$files = Translate::search_ext('(php|inc|js)');
		$files = array_filter($files, create_function('$file', 'return substr($file, 0, 9) != "./config/";'));
		$files = array_merge($files, Translate::search_ext('(php|dist)', 'config'));
		$sh = $GLOBALS['xgettext'] . ' --language=' . $language .
                  ' --keyword=_ --sort-output --force-po --copyright-holder="Horde Project"';
		if ($gettext_version[0] > 0 || $gettext_version[1] > 11) {
		    $sh .= ' --msgid-bugs-address="support@scopserv.com"';
		}
		$file = 'po' . '/' . $apps[$i] . '.pot';
		if (file_exists($file) && !is_writable($file)) {
		    Translate_Display::error((sprintf(_("%s is not writable."), $file)));
		}
		$tmp_file = $file . '.tmp.pot';
		$sh .= ' -o ' . $tmp_file . ' ' . implode(' ', $files);
		exec($sh);
	    }
	    
	    if (file_exists($tmp_file)) {
		$files = Translate::search_ext('html', 'templates');
		$tmp = fopen($file . '.templates', 'w');
		foreach ($files as $template) {
		    $fp = fopen($template, 'r');
		    $lineno = 0;
		    while (($line = fgets($fp, 4096)) !== false) {
			$lineno++;
			$offset = 0;
			while (($left = strpos($line, '<gettext>', $offset)) !== false) {
			    $left += 9;
			    $buffer = '';
			    $linespan = 0;
			    while (($end = strpos($line, '</gettext>', $left)) === false) {
				$buffer .= substr($line, $left);
				$left = 0;
				$line = fgets($fp, 4096);
				$linespan++;
				if ($line === false) {
				    Translate_Display::error((sprintf(_("<gettext> tag not closed in file %s.\nOpening tag found in line %d."), $template, $lineno)));
				    break 2;
				}
			    }
			    $buffer .= substr($line, $left, $end - $left);
			    fwrite($tmp, "#: $template:$lineno\n");
			    fwrite($tmp, 'msgid "' . str_replace(array('"', "\n"), array('\"', "\\n\"\n\""), $buffer) . "\"\n");
			    fwrite($tmp, 'msgstr ""' . "\n\n");
			    
			    $offset = $end + 10;
			}
		    }
		    fclose($fp);
		}
		fclose($tmp);
		$sh = $GLOBALS['msgcomm'] . " --more-than=0 --sort-output \"$tmp_file\" \"$file.templates\" --output-file \"$tmp_file\"";
		exec($sh);
		unlink($file . '.templates');
		
		if (file_exists($file)) {
		    $diff = array_diff(file($tmp_file), file($file));
		    $diff = preg_grep('/^"POT-Creation-Date:/', $diff, PREG_GREP_INVERT);
		}
	    }
	    
	    if (!file_exists($file) || count($diff)) {
		@unlink($file);
		rename($tmp_file, $file);
		Translate_Display::info(_("Updated!"));
	    } else {
		@unlink($tmp_file);
		Translate_Display::info(_("Not changed!"));
	    }
	}
    }
    
    function merge()
    {
	global $apps, $dirs, $lang, $module;
	
	$compendium = ' --compendium="' . HORDE_BASE . '/' . 'po' . '/' . 'compendium.po"';
	// $compendium = ' --compendium=' . $option[1];
	// $compendium = '';
	
	if (!isset($lang) && !empty($compendium)) {
	    Translate_Display::error(_("Error: No locale specified."));
	    Translate_Display::info();
	    usage();
	}
	
	Translate::cleanup();
	
	for ($i = 0; $i < count($dirs); $i++) {
	    if (!empty($module) && $module != $apps[$i]) {
		continue;
	    }
	    Translate_Display::header(sprintf(_("Merging translation for module %s..."), $apps[$i]));
	    $dir = $dirs[$i] . '/' . 'po' . '/';
	    if (empty($lang)) {
		$langs = get_languages($dirs[$i]);
	    } else {
		if (!@file_exists($dir . $lang . '.po')) {
		    Translate_Display::info(_("Skipped..."));
		    Translate_Display::info();
		    continue;
		}
		$langs = array($lang);
	    }
	    foreach ($langs as $locale) {
		Translate_Display::info(sprintf(_("Merging locale %s..."), $locale));
		$sh = $GLOBALS['msgmerge'] . ' --update -v' . $compendium . ' "' . $dir . $locale . '.po" "' . $dir . $apps[$i] . '.pot"';
		exec($sh);
		Translate_Display::info(_("Done!"));
	    }
	}
    }
    
    function compendium()
    {
	global $dirs, $lang, $module;
	
	$dir = HORDE_BASE . '/' . 'po' . '/';
	$add = '';
	if (!isset($lang)) {
	    Translate_Display::error(_("Error: No locale specified."));
	    Translate_Display::info();
	    usage();
	}
	Translate_Display::info(sprintf(_("Merging all %s.po files to the compendium... "), $lang));
	$pofiles = array();
	for ($i = 0; $i < count($dirs); $i++) {
	    $pofile = $dirs[$i] . '/' . 'po' . '/' . $lang . '.po';
	    if (file_exists($pofile)) {
		$pofiles[] = $pofile;
	    }
	}
	if (!empty($dir) && substr($dir, -1) != '/') {
	    $dir .= '/';
	}
	$sh = $GLOBALS['msgcat'] . ' --sort-output ' . implode(' ', $pofiles) . $add . ' > ' . $dir . 'compendium.po ';
	exec($sh, $out, $ret);
	
	if ($ret == 0) {
	    Translate_Display::info(_("Done!"));
	} else {
	    Translate_Display::error(_("Failed!"));
	}
    }
    
    function init()
    {
	global $module, $apps, $dirs, $lang, $module;
	
	if (empty($lang)) { $lang = getenv('LANG'); }
	for ($i = 0; $i < count($dirs); $i++) {
	    if (@file_exists($dirs[$i] . '/po/' . $lang . '.po')) {
		continue;
	    }
	    if (!empty($module) && $module != $apps[$i]) { continue; }
	    $package = ucfirst($apps[$i]);
	    $package_u = Horde_String::upper($apps[$i]);
	    @include $dirs[$i] . '/lib/version.php';
	    $version = eval('return(defined("' . $package_u . '_VERSION") ? ' . $package_u . '_VERSION : "???");');
	    Translate_Display::header(sprintf(_("Initializing module %s..."), $apps[$i]));
	    if (!@file_exists($dirs[$i] . '/po/' . $apps[$i] . '.pot')) {
		Translate_Display::error(_("Failed!"));
		Translate_Display::info(sprintf(_("%s not found. Run 'Extract' first."), $dirs[$i] . '/' . 'po' . '/' . $apps[$i] . '.pot'));
		continue;
	    }
	    $dir = $dirs[$i] . '/' . 'po' . '/';
	    $sh = $GLOBALS['msginit'] . ' --no-translator -i ' . $dir . $apps[$i] . '.pot ' .
              (!empty($lang) ? ' -o ' . $dir . $lang . '.po --locale=' . $lang : '');
	    
	    if (!empty($lang) && !OS_WINDOWS) {
		$pofile = $dirs[$i] . '/po/' . $lang . '.po';
		$sh .= "; sed 's/PACKAGE package/$package package/' $pofile " .
		  "| sed 's/PACKAGE VERSION/$package $version/' " .
		  "| sed 's/messages for PACKAGE/messages for $package/' " .
		  "| sed 's/Language-Team: none/Language-Team: i18n@lists.horde.org/' " .
		  "> $pofile.tmp";
	    }
	    exec($sh, $out, $ret);
	    rename($pofile . '.tmp', $pofile);
	    if ($ret == 0) {
		Translate_Display::info(_("Done!"));
	    } else {
		Translate_Display::error(_("Failed!"));
	    }
	    Translate_Display::info();
	}
    }
    
    function make()
    {
	global $apps, $dirs, $lang, $module;
	
	$compendium = HORDE_BASE . '/' . 'po' . '/' . 'compendium.po';
	$save_stats = true;
	
	$horde = array_search('horde', $dirs);
	$horde_msg = array();
	$stats_array = array();
	
	for ($i = 0; $i < count($dirs); $i++) {
	    if (!empty($module) && $module != $apps[$i]) continue;
	    Translate_Display::header(sprintf(_("Building MO files for module %s..."), $apps[$i]));
	    if (empty($lang)) {
		$langs = get_languages($dirs[$i]);
	    } else {
		if (!@file_exists($dirs[$i] . '/' . 'po' . '/' . $lang . '.po')) {
		    Translate_Display::info(_("Skipped..."));
		    Translate_Display::info();
		    continue;
		}
		$langs = array($lang);
	    }
	    foreach ($langs as $locale) {
		Translate_Display::info(sprintf(_("Building locale %s..."), $locale));
		$dir = $dirs[$i] . '/' . 'locale' . '/' . $locale . '/' . 'LC_MESSAGES';
		if (!is_dir($dir)) {
		    require_once 'System.php';
		    if (!@System::mkdir("-p $dir")) {
			Translate_Display::error(sprintf(_("Warning: Could not create locale directory for locale %s:"), $locale));
			Translate_Display::info($dir, false);
			Translate_Display::info();
			continue;
		    }
		}
		
		/* Convert to unix linebreaks. */
		$pofile = $dirs[$i] . '/' . 'po' . '/' . $locale . '.po';
		$fp = fopen($pofile, 'r');
		$content = fread($fp, filesize($pofile));
		fclose($fp);
		
		$content = str_replace("\r", '', $content);
		$fp = fopen($pofile, 'wb');
		fwrite($fp, $content);
		fclose($fp);
		
		/* Check PO file sanity. */
		$sh = $GLOBALS['msgfmt'] . " --check \"$pofile\" 2>&1";
		exec($sh, $out, $ret);
		if ($ret != 0) {
		    Translate_Display::error(_("Warning: an error has occured:"));
		    Translate_Display::info(implode("\n", $out));
		    Translate_Display::info();
		    if ($apps[$i] == 'horde') {
			continue 2;
		    }
		    continue;
		}
		
		/* Compile MO file. */
		$sh = $GLOBALS['msgfmt'] . ' --statistics -o "' . $dir . '/' . $apps[$i] . '.mo"';
		if ($apps[$i] != 'horde') {
		    $horde_po = $dirs[$horde] . '/' . 'po' . '/' . $locale . '.po';
		    if (!@is_readable($horde_po)) {
			Translate_Display::error(sprintf(_("Warning: the Horde PO file for the locale %s does not exist:"), $locale));
			Translate_Display::info($horde_po);
			Translate_Display::info();
			$sh .= $dirs[$i] . '/' . 'po' . '/' . $locale . '.po';
		    } else {
			$sh = "export LANG=C ; " . $GLOBALS['msgcomm'] . " --more-than=0 --sort-output \"$pofile\" \"$horde_po\" | $sh -";
		    }
		} else {
		    $sh .= $pofile;
		}
		$sh .= ' 2>&1';
		$out = '';

		exec($sh, $out, $ret);
		
		if ($ret == 0) {
		    Translate_Display::info(_("Done!"));
		    $messages = array(0, 0, 0);
		    if (preg_match('/(\d+) translated/', $out[0], $match)) {
			$messages[0] = $match[1];
			if (isset($horde_msg[$locale])) {
			    $messages[0] -= $horde_msg[$locale][0];
			    if ($messages[0] < 0) $messages[0] = 0;
			}
		    }
		    if (preg_match('/(\d+) fuzzy/', $out[0], $match)) {
			$messages[1] = $match[1];
			if (isset($horde_msg[$locale])) {
			    $messages[1] -= $horde_msg[$locale][1];
			    if ($messages[1] < 0) $messages[1] = 0;
			}
		    }
		    if (preg_match('/(\d+) untranslated/', $out[0], $match)) {
			$messages[2] = $match[1];
			if (isset($horde_msg[$locale])) {
			    $messages[2] -= $horde_msg[$locale][2];
			    if ($messages[2] < 0) $messages[2] = 0;
			}
		    }
		    if ($apps[$i] == 'horde') {
			$horde_msg[$locale] = $messages;
		    }
		    $stats_array[$apps[$i]][$locale] = $messages;
		} else {
		    Translate_Display::error(_("Failed!"));
		    exec($sh, $out, $ret);
		    Translate_Display::info(implode("\n", $out));
		}
		if (count($langs) > 1) {
		    continue;
		}
		
		/* Merge translation into compendium. */
		if (!empty($compendium)) {
		    Translate_Display::header(sprintf(_("Merging the PO file for %s to the compendium..."), $apps[$i]));
		    if (!empty($dir) && substr($dir, -1) != '/') {
			$dir .= '/';
		    }
		    $sh = $GLOBALS['msgcat'] . " --sort-output \"$compendium\" \"$pofile\" > \"$compendium.tmp\"";
		    $out = '';
		    exec($sh, $out, $ret);
		    @unlink($compendium);
		    rename($compendium . '.tmp', $compendium);
		    if ($ret == 0) {
			Translate_Display::info(_("Done!"));
		    } else {
			Translate_Display::error(_("Failed!"));
		    }
		}
		Translate_Display::info();
	    }
	}
	if (empty($module)) {
	    Translate_Display::header(_("Results:"));
	} else {
	    Translate_Display::header(_("Results (including Horde):"));
	}
	
	echo '<br />';
	echo '<table cellspacing=0 cellpadding=0 width=90%>';
	echo sprintf('<tr><td class="control" width="15%%"><b>%s</b></td><td class="control"><b>%s</b></td><td align="right"  width="5%%" class="control"><b>%s</b></td><td align="right"  width="5%%" class="control"><b>%s</b></td><td align="right"  width="5%%" class="control"><b>%s</b></td></tr>', 'Module', 'Language', 'Translated', 'Fuzzy', 'Untranslated');
	
	$i = 0;
	foreach($stats_array as $app => $info) {
	    foreach($info as $locale => $message) {
		echo sprintf('<tr class="item%d"><td>%s</td><td>%s</td><td align="right" >%s</td><td align="right" >%s</td><td align="right" >%s</td></tr>',
			     ($i++ %2), $app, $locale, $messages[0], $messages[1], $messages[2]);
	    }
	}
	echo '</table>';
	
	if ($save_stats) {
	    $fp = @fopen('/tmp/translation_stats.txt', 'w');
	    if ($fp) {
		fwrite($fp, serialize($stats_array));
		fclose($fp);
	    }
	}
    }
    
    function cleanup($keep_untranslated = false)
    {
	global $apps, $dirs, $lang, $module;
	
	for ($i = 0; $i < count($dirs); $i++) {
	    if (!empty($module) && $module != $apps[$i]) { continue; }
	    Translate_Display::header(sprintf(_("Cleaning up PO files for module %s..."), $apps[$i]));
	    if (empty($lang)) {
		$langs = get_languages($dirs[$i]);
	    } else {
		if (!@file_exists($dirs[$i] . '/' . 'po' . '/' . $lang . '.po')) {
		    Translate_Display::info(_("Skipped..."));
		    Translate_Display::info();
		    continue;
		}
		$langs = array($lang);
	    }
	    foreach ($langs as $locale) {
		Translate_Display::info(sprintf(_("Cleaning up locale %s..."), $locale));
		$pofile = $dirs[$i] . '/' . 'po' . '/' . $locale . '.po';
		$sh = $GLOBALS['msgattrib'] . ($keep_untranslated ? '' : ' --translated') . " --no-obsolete --no-fuzzy --force-po $pofile > $pofile.tmp";
		$out = '';
		exec($sh, $out, $ret);
		if ($ret == 0) {
		    @unlink($pofile);
		    rename($pofile . '.tmp', $pofile);
		    Translate_Display::info(_("Done!"));
		} else {
		    @unlink($pofile . '.tmp', $pofile);
		    Translate_Display::error(_("Failed!"));
		}
		Translate_Display::info();
	    }
	}
    }
}

