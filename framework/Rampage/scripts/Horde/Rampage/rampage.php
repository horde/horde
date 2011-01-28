#!@php_bin@
<?php
/**
 * Script to automatically create a new Horde application based on a database
 * table definition.
 *
 * Copyright 2003-2011 The Horde Project (http://www.horde.org/)
 *
 * For license info, see COPYING, for more information and a user guide see
 * README.
 *
 * @author  Karsten Fourmont <karsten@horde.org>
 * @package Rampage
 */

@define('ZOMBIE_BASE', dirname(__FILE__) . '/..');

require_once dirname(__FILE__) . '/lib/Application.php';
Horde_Registry::appInit('horde', array('authentication' => 'none'));

/* List of files that are parsed, converted and copied to output
 * directory.  for all these files default zitem->parameter string
 * conversions are done.  handlers t_* for these files are called
 * before the default conversion, see transform code. */
$files = array('COPYING',
               'edit.php',
               'index.php',
               'list.php',
               'view.php',
               'search.php',
               'test.php',
               'registry.stub',
               'config/conf.php',
               'config/conf.xml',
               'config/menu.php',
               'config/prefs.php',
               'docs/CHANGES',
               'docs/CREDITS',
               'docs/RELEASE_NOTES',
               'lib/Api.php',
               'lib/Zombie.php',
               'lib/base.php',
               'lib/Driver.php',
               'lib/Driver/sql.php',
               'locale/en/help.xml',
               'templates/common-header.inc',
               'templates/edit/edit.inc',
               'templates/list/empty.inc',
               'templates/list/footer.inc',
               'templates/list/header.inc',
               'templates/list/list_footers.inc',
               'templates/list/list_headers.inc',
               'templates/list/entry_summaries.inc',
               'templates/menu/menu.inc',
               'templates/search/search.inc',
               'templates/view/no-entry.inc',
               'templates/view/view.inc',
               );

/* These files are simply copied to the output directory. */
$rawfiles = array('graphics/zombie.png');

main();
exit;

/**
 * Prints usage info and exits.
 */
function print_usage_info()
{
    print "usage:\n"
        . "rampage.php table [-a app] [-d dsn] [-i item] [-s items] [-f file]   or\n"
        . "rampage.php rampageconf.php [-f]\n"
        . "\n"
        . "First version reads table info for database table >table< and creates a\n"
        . "config file zombieconf.php.\n"
        . "Second version creates horde application app from given config file.\n"
        . "Overwrites existing app if -f is specified.\n"
        . "\n"
        . "  Options for first type are:\n"
        . "  table        name of database table\n"
        . "  -a app       name of application to be created\n"
        . "  -d dsn       dsn (see pear/DB). If not specified, horde default is\n"
        . "               used. Otherwise driverconfig=custom is put into conf.php\n"
        . "  -i item      Name used for one table entry (like 'task').\n"
        . "               Defaults to 'item'. So menu entry for add will be called\n"
        . "               'New Item'\n"
        . "  -s setname   Name used for collection of table entries.\n"
        . "               defaults to <name for item>.'s'. So menu entry for list\n"
        . "               will be called 'List Items'\n"
        . " -f filename  name of the config file to write the results to.\n"
        . "               Defaults to zombieconf.php. Must end with .php\n";

    exit(3);
}

/**
 * Main functions. Just decides what mode we are in and calls the
 * appropriate methods.
 */
function main()
{
    global $info, $config;

    $args = Console_Getopt::readPHPArgv();

    if (count($args) < 2) {
        print_usage_info();
    }

    if (substr($args[1], 0, 1) == "-" || substr($args[1], 0, 1) == "/") {
        print "invalid parameter " . $args[2] . "\n";
        print_usage_info();
    }

    if (substr($args[1], -4) == ".php") {
        // mode 2: create zombie app
        if (!file_exists($args[1])) {
            die("config file " . $args[1] . " does not exist\n");
        }
        read_config_file($args[1]);
        $outdir = ZOMBIE_BASE . '/../' . strtolower($config['app']);
        if (is_dir($outdir) && $args[2] != "-f") {
            print "Directory $outdir already exists.\nUse -f flag to force overwrite\n";
            exit;
        }
        $n = $config['app'];
        print "Creating Horde Application $n in directory " . strtolower($n) . "\n";
        transform($outdir);
        print "\nHorde Application '$n' successfully written. Where to go from here:\n"
            ."1) Paste content of $n/registry.stub to horde/config/registry.php.\n"
            ."   After that, the $n should be working!\n"
            ."2) Replace $n.gif with proper application icon\n"
            ."3) Ensure conf.php is not world-readable as it may contain password data.\n"
            ."4) Start playing around and enhancing your new horde application. Enjoy!\n";

    } else { // mode 1: create config file
        parse_options($args);
        print "creating config file for table " . $config['table'] . "\n";
        create_table_info();
        enhance_info();
        print "writing config file to " . $config['file'] . "\n";
        dump_config_file();
    }
}

/**
 * Parse the command line options for mode 1: creation of config file
 * and sets the appropriate defaults. Result is a working $config
 * global.
 */
function parse_options($args)
{
    global $config;

    // set the defaults
    $config['app'] = 'app';
    $config['item'] = 'item';
    $config['file'] = 'zombieconf.php';
    $config['table'] = $args[1];

    for ($i = 0; $i < count($args); ++ $i) {
        switch ($args[$i]) {
        case "-d":
            $config['dsn'] = $args[$i +1];
            break;

        case "-a":
            $config['app'] = $args[$i +1];
            break;

        case "-i":
            $config['item'] = $args[$i +1];
            break;

        case "-s":
            $config['set'] = $args[$i +1];
            break;

        case "-f":
            $config['file'] = $args[$i +1];
            if (substr($config['file'], -4) != ".php") {
                $config['file'] .= '.php';
            }
            break;
        }
    }

    if (!$config['set']) {
        $config['set'] = $config['item'] . 's';
    }
}

/**
 * Does the actual database querying to get the tableinfo.
 * Based on pear's $db->tableInfo
 */
function create_table_info()
{
    global $info, $config, $conf;

    if ($config['dsn']) {
        $c = $config['dsn'];
    } else {
        $c = $conf['sql'];
    }

    $db = & DB :: connect($c);
    if (DB :: isError($db)) {
        die($db->getMessage() . "\n");
    }

    // store exploded dsn info in config:
    if ($config['dsn']) {
        $config['dsn'] = $db->dsn;
    }

    $info = $db->tableInfo($config['table']);
    if (is_a($info, 'PEAR_Error')) {
        die("error in calling tableInfo:" . $info->getMessage() . "\n");
    }

    $db->disconnect();
}

/**
 * Polish the $info global from create_table_info.  adds some
 * heuristic defaults.
 */
function enhance_info()
{
    global $info, $config;

    $title_field = field_get_title_field($info);

    for ($i = 0; $i < count($info); ++ $i) {
        // per default text fields are searchable:
        switch (strtolower($info[$i]['type'])) {
        // String types
        case 'string':
        case 'char':
        case 'varchar':
        case 'blob':
        case 'tinyblob':
        case 'tinytext':
        case 'mediumblob':
        case 'mediumtext':
        case 'longblob':
        case 'longtext':
            $info[$i]['search'] = 1;
            break;
        default :
            $info[$i]['search'] = 0;
        }

        // per default all non blob fields are displayed in list view
        if (is_blob($info[$i])) {
            $info[$i]['list'] = 0;
        } else {
            $info[$i]['list'] = 2;
        }

        // per default all fields are editable, except the primary_key
        // and timestamp fields
        $pk = field_get_primary_key();
        if ($info[$i]['name'] == $pk['name'] || strtolower($info[$i]['type']) == 'timestamp') {
            $info[$i]['edit'] = 0;
        } else {
            $info[$i]['edit'] = 1;
        }

        $info[$i]['view'] = 1; // view everything

        // Field description (displayed to user). Defaults to name.
        // Please note that underscores here result in hotkeys.
        $info[$i]['desc'] = ucwords($info[$i]['name']);

        // Set the flag for the title field.
        if ($info[$i]['name'] == $title_field['name']) {
            $info[$i]['flags'] .= ' title';
        }
    }
}

/**
 * Write the field and config information to the zombieconf.php config
 * file. The field-info array $info is written in a tabular format to
 * allow manual editing.
 */
function dump_config_file()
{
    global $info, $config;

    $fh = fopen($config['file'], 'w');

    fwrite($fh, "<?php\n");

    // write header line:
    $s = "//    ";
    $s .= print_cfgfield('Field', 'name', true) . ' ';
    $s .= print_cfgfield("Desc", "desc", true) . ' ';
    $s .= print_cfgfield("Type", "type", true) . ' ';
    $s .= print_cfgfield("l", "len", true) . ' ';
    $s .= "L V E S flags\n";
    $s .= '//    ';
    $s .= print_cfgfield('==================', 'name', true) . ' ';
    $s .= print_cfgfield('==================', 'desc', true) . ' ';
    $s .= print_cfgfield('==================', 'type', true) . ' ';
    $s .= print_cfgfield('=========', 'len', true) . ' ';
    $s .= "= = = = =====\n";

    fwrite($fh, "\$fields = array(\n$s");
    for ($i = 0; $i < count($info); ++ $i) {
        $s  = "array(";
        $s .= print_cfgfield($info[$i]['name'], 'name') . ',';
        $s .= print_cfgfield($info[$i]['desc'], 'desc') . ',';
        $s .= print_cfgfield($info[$i]['type'], 'type') . ',';
        $s .= print_cfgfield($info[$i]['len'], 'len') . ',';

        $s .= print_cfgfield($info[$i]['list'], 'list') . ',';
        $s .= print_cfgfield($info[$i]['view'], 'view') . ',';
        $s .= print_cfgfield($info[$i]['edit'], "edit") . ",";
        $s .= print_cfgfield($info[$i]['search'], 'search') . ',';
        $s .= print_cfgfield($info[$i]['flags'], 'flags');

        $s .= ($i == count($info) - 1) ? ")\n" : "),\n";
        fwrite($fh, $s);
    }

    fwrite($fh, "/*\nLegend:\nField= database column name\n");
    fwrite($fh, "Desc = Description of Field for Forms. Use _ to indicate Hotkeys!\n");
    fwrite($fh, "Type = Sql type\nl    = Length of database field\n");
    fwrite($fh, "L/V/E= Show field on List/View/Edit page. L=2 means show field and link to view page\n");
    fwrite($fh, "S    = Enable Search for this field\n");
    fwrite($fh, "flags= Field flags (space separated) from db::tableinfo, Use additional flag\n");
    fwrite($fh, "       'title' do indicate field to use as title on edit and view page\n*/");

    fwrite($fh, ");\n\n");
    fwrite($fh, "\$config['table'] = '" . $config['table'] . "';\n");
    fwrite($fh, "\$config['app']   = '" . strtolower($config['app']) . "'; // Name of application. Output will be written to this directory.\n");
    fwrite($fh, "\$config['item']  = '" . $config['item'] . "'; // Name used for one table entry (like 'task'). So menuy entry will have name like 'Edit task'\n");
    fwrite($fh, "\$config['set']   = '" . $config['set'] . "'; // Name used for collection of table entries (like 'tasks'). So menu entry for list will be called s.th. like 'List tasks'\n");

    if (is_array($config['dsn'])) {
        foreach ($config['dsn'] as $k => $v) {
            if ($v) {
                fwrite($fh, "\$config ['dsn']['$k']   = '$v';\n");
            }
        }
    }
    fwrite($fh, "?>\n");
    fclose($fh);
}

/**
 * Helper function to pad a given value from the info
 * array so its length equals the width of the column
 * defined by the longest value
 */
function print_cfgfield($value, $name, $is_header = false)
{
    global $colwidth, $info;

    if (!isset ($colwidth[$name])) {
        $colwidth[$name] = 0;
        for ($i = 0; $i < count($info); ++ $i) {
            if (strlen($info[$i][$name]) > $colwidth[$name]) {
                $colwidth[$name] = strlen($info[$i][$name]);
            }
        }
    }

    if ($is_header) {
        $len = $colwidth[$name] + (is_string($info[0][$name]) ? 2 : 0);
        return str_pad(substr($value, 0, $len), $len);
    }

    if (is_string($value)) {
        return str_pad("'$value'", $colwidth[$name] + 2);
    }

    return str_pad($value, $colwidth[$name]);
}

/* ***********************************************/
/*  mode 2: functions to create new application  */
/* ***********************************************/

/**
 * Read (by including) the config file.
 */
function read_config_file($fname)
{
    global $info, $config;

    require_once $fname;

    if (!is_array($fields) || !is_array($config)) {
        die("Fatal Error: $fname does not contain valid info and config arrays\n");
    }

    // convert info array back to assoc. array form:
    $info = array ();
    for ($i = 0; $i < count($fields); ++ $i) {
        $x = array ();
        list ($x['name'], $x['desc'], $x['type'], $x['len'], $x['list'], $x['view'], $x['edit'], $x['search'], $x['flags']) = $fields[$i];
        $info[$i] = $x;
    }
}

/**
 * Performs the actual copying/modifying of the files.
 *
 */
function transform($outdir)
{
    global $files, $rawfiles, $info, $config;

    $pk = field_get_primary_key();

    // search/replace arrays for default replacment of vocabulary:
    $search = array ('zitem_id',
                     'ZOMBIE', 'zombie', 'Zombie',
                     'zitems', 'Zitems',
                     'zitem', 'Zitem');

    $replace = array ($pk['name'],
                      strtoupper($config['app']), strtolower($config['app']), ucfirst($config['app']),
                      strtolower($config['set']), ucfirst($config['set']),
                      strtolower($config['item']), ucfirst($config['item']));

    foreach ($files as $file) {
        $infile = ZOMBIE_BASE . '/' . $file;
        // outfile may be renamed (zombie.php ->appname.php)
        $outfile = $outdir . '/' . str_replace($search, $replace, $file);
        mkdir_p(dirname($outfile));

        $c = file_get_contents($infile);

        // deduct handler function name from the file name:
        $handler = str_replace('.php', '', trim($file));
        $handler = str_replace('.inc', '', $handler);
        $handler = str_replace('/', '_', $handler);
        $handler = 't_' . $handler;

        // if handler is there, apply it:
        if (function_exists($handler)) {
            print "handler: $file\n";
            $c = $handler ($c);
        } else {
            print "copy   : $file\n";
        }

        //finally do default replacments and write file
        $c = str_replace($search, $replace, $c);

        $fh = fopen($outfile, "wb");
        if ($fh) {
            fwrite($fh, $c);
            fclose($fh);
        }
    }

    // all the tricky stuff is done, just do raw copy of graphics:
    foreach ($rawfiles as $file) {
        $infile = "../$file";
        // outfile is in outdir and maybe renamed (zombie.php ->appname.php)
        $outfile = $outdir . '/' . str_replace($search, $replace, $file);
        mkdir_p(dirname($outfile));
        echo ("rawcopy:   $file\n");
        copy($infile, $outfile);
    }
}

/**
 * Transformer-Handlers for individual files go here
 * Handlers are called before name replacment takes
 * place.
 */
function t_lib_Driver_sql($c)
{
    global $info;

    // create build_zentry function
    $s = '';
    foreach ($info as $i) {
        $s .= "\n        '" . $i[name] . "' => " . field_sql2php($i) . ',';
    }
    $s = substr($s, 0, -1); //print "$s\n";
    $c = preg_replace('/!!ZOMBIES!!/s', "    function _buildZitem(\$row) {\n"."        return array($s);\n"."    }\n", $c);

    //create insert sstatement:
    // a) fields:
    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        $s .= "        $n,\n";
    }
    $s = substr($s, 0, -2); // remove trailing ",\n"
    $c = preg_replace('/!!ZOMBIEFIELDS!!/', $s, $c);

    // b) tags
    $s = '';
    foreach ($info as $i) {
        $s .= field_get_printf_tag($i) . ',';
    }
    $s = substr($s, 0, -1); // remove trailing ",\n"
    $c = preg_replace('/!!ZOMBIETAGS!!/', $s, $c);

    // c) values
    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        $s .= '        ' . field_get_quoted($i) . ",\n";
    }
    $s = substr($s, 0, -2); // remove trailing ",\n"
    $c = preg_replace('/!!ZOMBIEVALUES!!/', $s, $c);

    //create update code:
    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        $s .= "        if (\$zitem['$n'] !== null) {\n" . "            \$query .= sprintf('$n = %s, '," . field_get_quoted($i) . ");\n" . "        }\n";
    }

    $s .= "\n        if (!\$query) return; // nothing to do\n\n";
    $pk = field_get_primary_key();

    // for the WHERE part in the update clause, we need the value
    // $zitem_id rather than $zitem['zitem_id'] as it is possible to change
    // this field during an update. So we manually compile this:
    $x = field_get_quoted($pk);
    $x = str_replace("\$zitem['" . $pk['name'] . "']", "\$zitem_id", $x);

    $s .= "        \$query = sprintf('UPDATE %s SET %s WHERE zitem_id = " . field_get_printf_tag($pk) . "',\n               \$this->_params['table'],substr(\$query,0,-2),\n                " . $x . ");\n";

    return preg_replace('/!!ZOMBIEUPDATE!!/', $s, $c);
}

function t_templates_list_entry_summaries($c)
{
    global $info;

    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        if ($i['list'] == 2) {
            $s .= "  <td><?php \$link = empty(\$zitem['$n']) ? 'link' : 'linkTooltip'; echo Horde::\$link(\$zitem['view_link'], _(\"View Details\"), \$tstyle, '', '') . (isset(\$zitem['$n']) ? " . render_field($i) . " : _(\"[none]\")) ?></a></td>\n";
        } else if ($i['list'] == 1) {
                $s .= "    <td><?php echo \$zitem['$n'] ?></td>\n";
            }
    }
    $s = substr($s, 0, -1);
    //print "$s\n";
    $c = preg_replace('/!!ZOMBIES!!/', $s, $c);

    return $c;
}

function t_templates_list_list_headers($c)
{

    global $info;

    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        if ($i['list']) {
            $s .= "<th class=\"<?php echo (\$sortby == ZOMBIE_SORT_$n) ? 'selected' : 'item' ?>\" width=\"2%\">
                <?php if (\$sortby == ZOMBIE_SORT_$n) echo Horde::link(Horde::url(Horde_Util::addParameter(\$sortbyurl, 'sortby', ZOMBIE_SORT_$n)), _(\"Change sort direction\"), 'widget') . Horde::img(\$sortdir ? 'za.gif' :  'az.gif', _(\"Change sort direction\"), null, \$registry->get('graphics', 'horde')) ?></a>
                <?php echo Horde::widget(Horde::url(Horde_Util::addParameter('list.php', 'sortby', ZOMBIE_SORT_$n)), _(\"Sort by User Name\"), 'widget', '', '', _(\"" . $i['desc'] . "\")) ?></a>&nbsp;</th>\n";
        }
    }

    return preg_replace('/!!ZOMBIES!!/', $s, $c);
}

function t_lib_Zombie($c)
{
    global $info;

    // create sorting constants
    $s = '';
    for ($i = 0; $i < count($info); ++ $i) {
        $n = $info[$i]['name']; // shortcut

        $s .= "/** @const ZOMBIE_SORT_$n Sort by zitem $n.           */ define('ZOMBIE_SORT_$n', $i);\n";
    }

    $c = preg_replace('/!!ZOMBIESORTCONST!!/', $s, $c);

    // create sorting function array
    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        $s .= "        ZOMBIE_SORT_$n => 'By_$n',\n";
    }

    $s = substr($s, 0, -2); // remove trailing ","
    $c = preg_replace('/!!ZOMBIESORTFUNCTIONARRAY!!/', $s, $c);

    // create sorting functions
    $s = '';
    foreach ($info as $i) {
        $s .= "
                            /**
                             * Comparison function for sorting zitems by " . $i['name'] . "
                             *
                             * @param array \$a  Zitem one.
                             * @param array \$b  Zitem two.
                             *
                             * @return integer  1 if zitem one is greater, -1 if zitem two is greater; 0 if they are equal.
                             */
                            function _sortBy_" . $i['name'] . "(\$a, \$b)
                            {
                                if (\$a['" . $i['name'] . "'] == \$b['" . $i['name'] . "']) return 0;
                                return (\$a['" . $i['name'] . "'] > \$b['" . $i['name'] . "']) ? 1 : -1;
                            }

                            /**
                             * Comparison function for reverse sorting zitems by " . $i['name'] . "
                             *
                             * @param array \$a  Zitem one.
                             * @param array \$b  Zitem two.
                             *
                             * @return integer  -1 if zitem one is greater, 1 if zitem two is greater; 0 if they are equal.
                             */
                            function _rsortBy_" . $i['name'] . "(\$a, \$b)
                            {
                                if (\$a['".$i['name']."'] == \$b['".$i['name']."']) return 0;
                                return (\$a['" . $i['name']."'] > \$b['" . $i['name'] . "']) ? -1 : 1;
                            }\n\n";

    }
    $c = preg_replace('/!!ZOMBIESORTS!!/', $s, $c);

    return $c;
}

function t_templates_view_view($c)
{

    global $info;

    $s = '';
    foreach ($info as $i) {
        if ($i['view']) {
            $n = $i['name']; // shortcut
            $s .= "<?php if (isset(\$zitem['$n'])): ?><tr>
                                          <td class=\"item<?php echo (\$i   %2) ?>\" align=\"right\" valign=\"top\" nowrap=\"nowrap\"><strong><?php echo _(\"$n\") ?></strong>&nbsp;</td>
                                          <td class=\"item<?php echo (\$i++ %2) ?>\" width=\"100%\"><?php echo ".render_field($i)." ?></td>
                                          </tr>
                                          <?php endif; ?>
                                    ";
        }
    }
    $s = substr($s, 0, -1); // remove trailing ","
    $c = preg_replace('/!!ZOMBIES!!/', $s, $c);

    // get title field
    $x = field_get_title_field();
    $c = preg_replace('/!!ZOMBIENAME!!/', $x['name'], $c);

    return $c;
}

function t_edit($c)
{
    global $info;

    // create default value for new entries
    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        $s .= "        '$n' => ".field_default($i).",\n";
    }

    $s = substr($s, 0, -2); // remove trailing ","
    $c = preg_replace('/!!ZOMBIES!!/', $s, $c);

    // get title field
    $x = field_get_title_field();
    $c = preg_replace('/!!ZOMBIENAME!!/', $x['name'], $c);

    // create code for form submit field retrieval:
    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        if (is_datetime($i)) {
            $s .= "        \$zitem['$n'] = Zombie::getDateTime(Horde_Util::getFormData('$n'));\n";
        } else if (is_boolean($i)) {
                $s .= "        \$zitem['$n'] = Horde_Util::getFormData('$n') ? 1 : 0 ;\n";
            } else {
                $s .= "        \$zitem['$n'] = Horde_Util::getFormData('$n');\n";
            }
    }

    return preg_replace('/!!ZOMBIES2!!/', $s, $c);
}

function t_templates_edit_edit($c)
{
    global $info;

    $s = '';
    foreach ($info as $i) {
        if ($i['edit']) {
            $n = $i['name']; // shortcut
            $d = $i['desc']; // shortcut
            $s .= "
                                        <tr>
                                        <td class=\"item\" align=\"right\" valign=\"top\"><strong><?php echo Horde::label('$n', _(\"$d\")) ?></strong>&nbsp;</td>
                                        <td class=\"item\" width=\"100%\">
                                          ".render_edit($i)."
                                        </td>\n</tr>";
        }
    }

    return preg_replace('/!!ZOMBIES!!/', $s, $c);
}

function t_list($c)
{
    global $info;

    $s = '';
    foreach ($info as $i) {
        if ($i['search']) {
            $n = $i['name']; // shortcut
            $s .= "    \$search_$n = (Horde_Util::getFormData('search_$n') == 'on');\n";
        }
    }
    $c = preg_replace('/!!ZOMBIES1!!/', $s, $c);

    $s = '';
    foreach ($info as $i) {
        if ($i['search']) {
            $n = $i['name']; // shortcut
            $s .= "                (\$search_$n && preg_match(\$pattern, \$zitem['$n'])) ||\n";
        }
    }
    if ($s) {
        $s = substr($s, 0, -3); // remove trailing "||\n"
        $c = preg_replace('/!!ZOMBIES2!!/', $s, $c);
    } else {
        // no search: just ensure valid syntax
        $c = preg_replace('/!!ZOMBIES2!!/', "false", $c);
    }

    return $c;
}

function t_templates_search_search($c)
{
    global $info;

    $s = '';
    foreach ($info as $i) {
        if ($i['search']) {
            $n = $i['name']; // shortcut
            $s .= "<input id=\"$n\" name=\"search_$n\" type=\"checkbox\" checked=\"checked\" />"."<?php echo Horde::label('$n', _(\"$n\")) ?><br>\n";
        }
    }
    $s = substr($s, 0, -1); // remove trailing ","
    $c = preg_replace('/!!ZOMBIES!!/', $s, $c);

    return $c;
}

function t_config_prefs($c)
{

    global $info;

    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        $s .= "        ZOMBIE_SORT_$n => _(\"$n\"),\n";
    }
    $s = substr($s, 0, -2); // remove trailing ",\n"
    $c = preg_replace('/!!ZOMBIES!!/', $s, $c);

    return $c;
}

function t_config_conf($c)
{
    global $info, $config;

    $s = "\$conf['storage']['params']['table'] = '" . $config['table'] . "';\n" .
         "\$conf['storage']['driver'] = 'sql';\n";

    if (is_array($config['dsn'])) {
        $s .= "\$conf['storage']['params']['driverconfig'] = 'custom';\n";
        foreach ($config['dsn'] as $k => $v) {
            if ($v) {
                $s .= "\$conf['storage']['params']['$k']   = '$v';\n";
            }
        }
    } else {
        $s .= "\$conf['storage']['params']['driverconfig'] = 'horde';\n";
    }
    $c = preg_replace('/!!ZOMBIES!!/', $s, $c);

    return $c;
}

/**
 * template for additional handlers:
 */
function t_($c)
{
    global $info;

    $s = '';
    foreach ($info as $i) {
        $n = $i['name']; // shortcut
        $s .= ',';
    }
    $s = substr($s, 0, -1); // remove trailing ","
    // print "$s\n";  // debug only
    $c = preg_replace('/!!ZOMBIES!!/', $s, $c);

    return $c;
}

//
// Field Helper Functions
//

/**
 * render a field for output
 */
function render_field($field)
{
    $n = $field['name']; // shortcut

    switch (strtolower($field['type'])) {
    // String types
    case 'string':
    case 'char':
    case 'varchar':
        return "htmlspecialchars(\$zitem['$n'])";

    // Blobs:
    case 'blob':
    case 'tinyblob':
    case 'tinytext':
    case 'mediumblob':
    case 'mediumtext':
    case 'longblob':
    case 'longtext':
        return "nl2br(Horde_Text::linkUrls(\$GLOBALS['injector']->getInstance('Horde_Core_Factory_TextFilter')->filter(\$zitem['$n'], 'space2html', array('encode' => true)), false, 'text'))";

    case 'bool':
    case 'boolean':
    case 'bit':
        return "(\$zitem['$n'] ? Horde::img('checked.gif', _(\"True\")) : Horde::img('unchecked.gif', _(\"False\")))";

    // Integer types
    case 'int':
    case 'smallint':
    case 'mediumint':
    case 'bigint':
    case 'tinyint':
    case 'integer':

    // Float types
    case 'float':
    case 'double':
    case 'real':
    case 'dec':
    case 'decimal':
    case 'numeric':
    case 'fixed':
        return "\$zitem['$n']";

    // Date/Time Types
    case 'date':
    case 'unixdate':
        return "strftime(\$prefs->getValue('date_format'), \$zitem['$n'])";

    case 'datetime':
    case 'timestamp':
    case 'unixepoch':
        return "strftime(\$prefs->getValue('date_format') . ' %H:%M', \$zitem['$n'])";

    case 'unixtime':
    case 'time':
        return "strftime('%H:%M:%S', \$zitem['$n'])";

    case 'year':
        return "\$zitem['$n']";

    default :
        die("Unknown field type: ".$field['type']."! Sorry, no implementation yet\n");
    }
}

/**
 * Creates an html input widget for the given field.
 */
function render_edit($field)
{
    $n = $field['name']; // shortcut

    switch (strtolower($field['type'])) {
    // String types
    case 'string':
    case 'char':
    case 'varchar':
        return "<input id=\"$n\" name=\"$n\" type=\"text\" value=\"<?php echo htmlspecialchars(\$zitem['$n']) ?>\" size=\"50\" maxlength=\"" . $field['len'] . "\" />";

    // Blobs:
    case 'blob':
    case 'tinyblob':
    case 'tinytext':
    case 'mediumblob':
    case 'mediumtext':
    case 'longblob':
    case 'longtext':
        return "<textarea id=\"$n\" name=\"$n\" cols=\"50\" rows=\"5\"><?php echo htmlspecialchars(\$zitem['$n']) ?></textarea>";

    case 'bit':
    case 'bool':
    case 'boolean':
        return "<input type=\"checkbox\" name=\"$n\" value=\"1\" <?php if(\$zitem['$n']) print \"CHECKED\" ?>/>";

    // Integer types
    case 'int':
    case 'smallint':
    case 'mediumint':
    case 'bigint':
    case 'tinyint':
    case 'integer':
    // Float types
    case 'float':
    case 'double':
    case 'real':
    case 'dec':
    case 'decimal':
    case 'numeric':
    case 'fixed':
        return "<input id=\"$n\" name=\"$n\" type=\"text\" value=\"<?php echo htmlspecialchars(\$zitem['$n']) ?>\" size=\"50\" maxlength=\"" . $field['len'] . "\" />";

    // Date/Time Types
    case 'date':
    case 'unixdate':
        return "<?php echo Zombie::buildDateWidget('$n',\$zitem['$n']) ?>";

    case 'datetime':
    case 'timestamp':
    case 'unixepoch':
        return "<?php echo Zombie::buildDateWidget('$n',\$zitem['$n']) ?>
                <p>
                <?php echo Zombie::buildTimeWidget('$n',\$zitem['$n']) ?>";

    case 'time':
    case 'unixtime':
        return "<?php echo Zombie::buildTimeWidget('$n',\$zitem['$n']) ?>";

    case 'year':
        return "<input id=\"$n\" name=\"$n\" type=\"text\" value=\"<?php echo htmlspecialchars(\$zitem['$n']) ?>\" size=\"4\" maxlength=\"" . $field['len'] . "\" />";

    default:
        die("Unknown field type: ".$field['type']."! Sorry, no implementation yet\n");
    }
}

/**
 * Returns the default value for this field.
 * Unfortunately this info is not provided
 * by pear's tableinfo function
 * so this is currently either mantually set or useless
 */
function field_default($field)
{

    if (isset ($field['default'])) {
        return 1 * $field['default'];
    }

    return "''";
}

/**
 * returns true for blob fields
 */
function is_blob($field)
{
    switch (strtolower($field['type'])) {
    case 'blob':
    case 'tinyblob':
    case 'tinytext':
    case 'mediumblob':
    case 'mediumtext':
    case 'longblob':
    case 'longtext':
        return true;
    }

    return false;
}

/**
 * Returns true if $field is a date or time field.
 */
function is_datetime($field)
{
    switch (strtolower($field['type'])) {
    case 'date':
    case 'datetime':
    case 'timestamp':
    case 'time':
    case 'year':
    case 'unixdate':
    case 'unixtime':
    case 'unixepoch':
        return true;
    }
    return false;
}

/**
 * returns true if $field is a boolean field
 */
function is_boolean($field)
{
    switch (strtolower($field['type'])) {
    case 'bit':
    case 'bool':
    case 'boolean':
        return true;
    }
    return false;
}

/**
 * returns the field that can be considered the "name" of the entry.
 * This name field is used as the headline for the view and edit pages.
 * These display a single entry.
 */
function field_get_title_field()
{
    global $info;

    // first look for flag 'title'
    foreach ($info as $i) {
        if (stristr($i['flags'], 'title')) {
            return $i;
        }
    }

    // not explicitly set, look for field containing "name" string:
    foreach ($info as $i) {
        if (stristr($i['name'], 'name')) {
            return $i;
        }
    }

    // still no field: use first one:
    return $info[0];
}

/**
 *  Returns the field that is primary key.
 */
function field_get_primary_key()
{
    global $info;
    foreach ($info as $i) {
        if (stristr($i['flags'], "primary_key")) {
            return $i;
        }
    }
    die("unable to find primary key field. one flags entry must contain the string 'primary_key'\n");
}

/**
 * creates rhs of an assignment, does sql->php conversion for fields.
 * Converts Charsets for string, and sql date/time to unix epoch
 * For most fields just \$row[fieldname].
 * Opposite of field_get_quoted
 */
function field_sql2php($field)
{
    switch (strtolower($field['type'])) {
    // String types
    case 'string':
    case 'blob':
    case 'char':
    case 'varchar':
    case 'tinyblob':
    case 'tinytext':
    case 'mediumblob':
    case 'mediumtext':
    case 'longblob':
    case 'longtext':
        return "Horde_String::convertCharset(\$row['".$field["name"]."'], \$this->_params['charset'], 'UTF-8')";

    // Integer types
    case 'bit':
    case 'bool':
    case 'boolean':
    case 'int':
    case 'smallint':
    case 'mediumint':
    case 'bigint':
    case 'tinyint':
    case 'integer':

    // Float types
    case 'float':
    case 'double':
    case 'real':
    case 'dec':
    case 'decimal':
    case 'numeric':
    case 'fixed':
    case 'unixdate':
    case 'unixtime':
    case 'unixepoch':
        return "\$row['" . $field['name'] . "']";

    // Date/Time Types
    case 'date':
    case 'datetime':
    case 'timestamp':
    case 'time':
    case 'year':
        return "\$this->sqlDateTime2Epoch(\$row['".$field["name"]."'])";

    default:
        die("Unknown field type: ".$field['type']."! Sorry, no implementation yet\n");
    }
}

/**
 * Returns the printf tag for the given field.
 * %s for strings, %d for decimal etc.
 */
function field_get_printf_tag($field)
{
    switch (strtolower($field['type'])) {
    // String types
    case 'string':
    case 'blob':
    case 'char':
    case 'varchar':
    case 'tinyblob':
    case 'tinytext':
    case 'mediumblob':
    case 'mediumtext':
    case 'longblob':
    case 'longtext':
        return '%s';

    // Integer types
    case 'bit':
    case 'bool':
    case 'boolean':
    case 'int':
    case 'smallint':
    case 'mediumint':
    case 'bigint':
    case 'tinyint':
    case 'integer':
    case 'unixdate':
    case 'unixtime':
    case 'unixepoch':
        return '%d';

    // Float types
    case 'float':
    case 'double':
    case 'real':
    case 'dec':
    case 'decimal':
    case 'numeric':
    case 'fixed':
        return '%f';

    // Date/Time Types
    case 'date':
    case 'datetime':
    case 'timestamp':
    case 'time':
    case 'year':
        return '%s';

    default:
        die("Unknown field type: ".$field['type']."! Sorry, no implementation yet\n");
    }
}

/**
 * Gets a quote represention of the field's value
 * for use in a sql statement. Does format and charset conversions.
 * opposite of field_sql2php
 */
function field_get_quoted($field)
{
    $n = $field['name']; // shortcut

    switch (strtolower($field['type'])) {
    // String types
    case 'string':
    case 'blob':
    case 'char':
    case 'varchar':
    case 'tinyblob':
    case 'tinytext':
    case 'mediumblob':
    case 'mediumtext':
    case 'longblob':
    case 'longtext':
        return "Horde_String::convertCharset(\$this->_db->quote(\$zitem['$n']), 'UTF-8', \$this->_params['charset'])";

    // Integer types
    case 'bit':
    case 'bool':
    case 'boolean':
    case 'int':
    case 'smallint':
    case 'mediumint':
    case 'bigint':
    case 'tinyint':
    case 'integer':
    case 'unixdate':
    case 'unixtime':
    case 'unixepoch':
        return "intval(\$zitem['$n'])";

    // Float types
    case 'float':
    case 'double':
    case 'real':
    case 'dec':
    case 'decimal':
    case 'numeric':
    case 'fixed':
        return "floatval(\$zitem['$n'])";

    // Date/Time Types
    case 'datetime':
    case 'timestamp':
        return "\$this->_db->quote(date('Y-m-d H:i:s',\$zitem['$n']))";

    case 'date':
        return "\$this->_db->quote(date('Y-m-d',\$zitem['$n']))";

    case 'time':
        return "\$this->_db->quote(date('H:i:s',\$zitem['$n']))";

    case 'year':
        return "\$this->_db->quote(date('Y',\$zitem['$n']))";

    default :
        die("Unknown field type: " . $field['type'] . "! Sorry, no implementation yet\n");
    }
}

/**
 * mkdir -p replacement.
 * php<5 does not hav a mkdir -p function (create dirs recursively)
 * so use this implementation from  saint at corenova.com
 * found at www.php.net/mkdir
 */
function mkdir_p($target)
{
    if (is_dir($target) || empty ($target)) {
        return 1; // best case check first
    }
    if (file_exists($target) && !is_dir($target)) {
        return 0;
    }
    if (mkdir_p(substr($target, 0, strrpos($target, '/')))) {
        return mkdir($target); // crawl back up & create dir tree
    }
    return 0;
}
