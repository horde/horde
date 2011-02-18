<?php
/**
 * This file contains all the configuration information for the various
 * languages that are supported. Documentation is inline.
 *
 * IMPORTANT: Local overrides should be placed in languages.local.php, or
 * languages-servername.php if the 'vhosts' setting has been enabled in Horde's
 * configuration.
 */
$languages = array(

    // Format is
    // Language name, filepattern regexp, module to invoke, (optional) tabwidth
    // Note that to have another language supported by Generic.php,
    // you must ensure that:
    // a) exuberant ctags supports it
    // b) generic.conf is updated to specify information about the language
    // c) the name of the language given here matches the entry in generic.conf
    'filetype' => array(
        'C'      => array('C', '\.c$', 'Generic', '8'),
        'C++'    => array('C++', '\.C$|((?i)\.c\+\+$|\.cc$|\.cpp$|\.cxx$|\.h$|\.hh$|\.hpp$|\.hxx$|\.h\+\+$)',
                          'Generic', '8'),
        'Java'   => array('Java', '(?i)\.java$', 'Generic', '4'),
         // No tabwidth specified here as an example
        'Make'   => array('Make', '(?i)\.mak$|makefile*', 'Generic'),
        'Perl'   => array('Perl', '(?i)\.pl$|\.pm$|\.cgi$|\.perl$', 'Generic', '4'),
        'PHP'    => array('php', '(?i)\.php$|\.php3$|\.phtml|\.phpt|\.phput$', 'Generic', '2'),
        'Python' => array('Python', '(?i)\.py$|\.python$', 'Generic', '4'),
    ),

    // Maps interpreter names to languages.  The format is:
    //  regexp => langname
    //   regexp is matched against the part after #! on the first line of a file
    //   langname must match one of the keys in filetype above.
    //
    // This mapping is only used if the filename doesn't match a pattern above, so
    // a shell script called shell.c will be recognised as a C file, not a shell file.
    'interpreters' => array(
        'perl'   => 'Perl',
        'php'    => 'PHP',
        'python' => 'Python'
    ),

    // How to map a language name to the ectags language-force name
    // if there is no mapping, then the language name is used
    'eclangnamemapping' => array(
        'C'      => 'c',
        'C++'    => 'c++',
        'Python' => 'python'
    ),

    // Options to always feed to ectags
    'ectagsopts' => '--c-types=+px --eiffel-types=+l --fortran-types=+L',

    // lang map specifies info for each language
    // what the reserved words & comment chars are
    'langmap' => array(
               'C' => array(
                       'reserved' => array(
                                      'auto', 'break', 'case', 'char', 'const',
                                      'continue', 'default', 'do', 'double',
                                      'else', 'enum', 'extern', 'float', 'for',
                                      'goto', 'if', 'int', 'long', 'register',
                                      'return', 'short', 'signed', 'sizeof',
                                      'static', 'struct', 'switch', 'typedef',
                                      'union', 'unsigned', 'void', 'volatile',
                                      'while',
                                     ),

                       'spec' => array('atom',     '\\\\.',     '',
                                  'comment',  '/\*',        '\*/',
                                  'comment',  '//',         "\$",
                                  'string',   '"',          '"',
                                  'string',   "'",          "'",
                                  'include',  '#\s*include',    "\$"),

                       'typemap' => array(
                                     'c' => 'class',
                                     'd' => 'macro (un)definition',
                                     'e' => 'enumerator',
                                     'f' => 'function definition',
                                     'g' => 'enumeration name',
                                     'm' => 'class, struct, or union member',
                                     'n' => 'namespace',
                                     'p' => 'function prototype or declaration',
                                     's' => 'structure name',
                                     't' => 'typedef',
                                     'u' => 'union name',
                                     'v' => 'variable definition',
                                     'x' => 'extern or forward variable declaration',
                                     'i' => 'interface'),
                       'langid' => '1',
                      ),

               'C++' => array(
                         'reserved' => array('and', 'and_eq', 'asm', 'auto', 'bitand',
                                        'bitor', 'bool', 'break', 'case', 'catch',
                                        'char', 'class', 'const', 'const_cast',
                                        'continue', 'default', 'delete', 'do',
                                        'double', 'dynamic_cast', 'else', 'enum',
                                        'explicit', 'export', 'extern', 'false',
                                        'float', 'for', 'friend', 'goto', 'if',
                                        'inline', 'int', 'long', 'mutable',
                                        'namespace', 'new', 'not', 'not_eq',
                                        'operator', 'or', 'or_eq', 'private',
                                        'protected', 'public', 'register',
                                        'reinterpret_cast', 'return', 'short',
                                        'signed', 'sizeof', 'static',
                                        'static_cast','struct', 'switch',
                                        'template','this', 'throw', 'true','try',
                                        'typedef', 'typeid','typename',
                                        'union', 'unsigned','using',
                                        'virtual', 'void','volatile',
                                        'wchar_t', 'while','xor',
                                        'xor_eq'),

                         'spec' => array('atom',     '\\\\.',       '',
                                  'comment',  '/\*',        '\*/',
                                  'comment',  '//',         "\$",
                                  'string',   '"',          '"',
                                  'string',   "'",          "'",
                                    'include',  '#\s*include',  "\$"),
                       'typemap' => array(
                                     'c' => 'class',
                                     'd' => 'macro (un)definition',
                                     'e' => 'enumerator',
                                     'f' => 'function definition',
                                     'g' => 'enumeration name',
                                     'm' => 'class, struct, or union member',
                                     'n' => 'namespace',
                                     'p' => 'function prototype or declaration',
                                     's' => 'structure name',
                                     't' => 'typedef',
                                     'u' => 'union name',
                                     'v' => 'variable definition',
                                     'x' => 'extern or forward variable declaration',
                                     'i' => 'interface'),
                         'langid' => '2',

                        ),

               'Java' => array(
                          'reserved' => array('break', 'case', 'continue', 'default',
                                         'do', 'else', 'for', 'goto', 'if',
                                         'return', 'static', 'switch', 'void',
                                         'volatile', 'while', 'public', 'class',
                                         'final', 'private', 'protected',
                                         'synchronized', 'package', 'import',
                                         'boolean', 'byte', 'new', 'abstract',
                                         'extends', 'implements', 'interface',
                                         'throws', 'instanceof', 'super', 'this',
                                         'native', 'null'),

                          'spec' => array('atom',      '\\\\.', '',
                                     'comment', '/\*',      '\*/',
                                     'comment', '//',       "\$",
                                     'string',  '"',        '"',
                                     'string',  "'",        "'",
                                     'include', 'import',   "\$",
                                     'include', 'package', "\$"
                                    ),
                          'typemap' => array(
                                        'c' => 'class',
                                        'f' => 'field',
                                        'i' => 'interface',
                                        'm' => 'method',
                                        'p' => 'package',
                                       ),
                          'langid' => '3',
                         ),

               'Fortran' => array(
                             'reserved' => array(),
                             'typemap' => array(
                                           'b' => 'block data',
                                           'c' => 'common block',
                                           'e' => 'entry point',
                                           'f' => 'function',
                                           'i' => 'interface',
                                           'k' => 'type component',
                                           'l' => 'label',
                                           'L' => 'local and common block variable',
                                           'm' => 'module',
                                           'n' => 'namelist',
                                           'p' => 'program',
                                          ),
                             'langid' => '4',
                            ),

               'Pascal' => array(
                            'reserved' => array(),
                            'langid' => '5',
                           ),

               'COBOL' => array(
                           'reserved' => array(),
                           'langid' => '6',
                          ),
               'Perl' => array(
                          'reserved' => array(
                                         'sub',
                                        ),
                          'spec' => array('atom',       '\$\W?',    '',
                                     'atom',        '\\\\.',    '',
                                     'include', '\buse\s+', ';',
                                     'include', '\brequire\s+', ';',
                                     'string',  '"',        '"',
                                     'comment', '#',        "\$",
                                     'comment', "^=\\w+",   "^=cut",
                                     'string',  "'",        "'"),
                          'typemap' => array(
                                        's' => 'subroutine',
                                        'p' => 'package',
                                       ),
                          'langid' => '7',

                         ),
               'Python' => array(
                            'reserved' => array('def','print','del','pass',
                                           'break','continue','return',
                                           'raise','import','from',
                                           'global','exec','assert',
                                           'if','elif','else','while',
                                           'for','try','except','finally',
                                           'class','as','import','or',
                                           'and','is','in','for','if',
                                           'not','lambda','self',
                                          ),

                            'spec' => array('comment',  '#',        "\$",
                                       'string',    '"',        '"',
                                       'string',    "'",        "'",
                                       'atom',      '\\\\.',    ''),
                            'typemap' => array(
                                          'c' => 'class',
                                          'f' => 'function',
                                         ),
                            'langid' => '8',
                           ),
               'php' => array(
                         'reserved' => array('and','$argv','$argc','break','case','class',
                                             'continue','default','do','die','echo','else',
                                             'elseif','empty','endfor','endforeach','endif',
                                             'endswitch','endwhile','E_ALL','E_PARSE','E_ERROR',
                                             'E_WARNING','exit','extends','FALSE','for','foreach',
                                             'function','HTTP_COOKIE_VARS','HTTP_GET_VARS',
                                             'HTTP_POST_VARS','HTTP_POST_FILES','HTTP_ENV_VARS',
                                             'HTTP_SERVER_VARS','GLOBALS','_FILES','_ENV','_REQUEST',
                                             '_GET','_POST','_COOKIE','_SESSION','if','global','list',
                                             'new','not','NULL','or','parent','PHP_OS','PHP_SELF',
                                             'PHP_VERSION','print','return','static','switch','stdClass',
                                             'this','TRUE','var','xor','virtual','while','__FILE__',
                                             '__LINE__','__sleep','__wakeup', 'header', 'global',
                                             'array', 'double', 'fclose', 'fopen', 'fputs', 'object',
                                             'is_a', 'in_array', 'is_null', 'unset', 'true', 'false',
                                             'null', 'define'
                                             ),

                         'spec' => array('comment',  '/\*',       '\*/',
                                    'comment',  '//',            "\$",
                                    'comment',  '#',             "\$",
                                    'string',   '"',             '"',
                                    'string',   "'",             "'",
                                    'include',  'require_once[^[a-zA-Z0-9_\x7f-\xff]', ";",
                                    'include',  'include_once[^[a-zA-Z0-9_\x7f-\xff]', ";",
                                    'include',  'require[^[a-zA-Z0-9_\x7f-\xff]',      ";",
                                    'include',  'include[^[a-zA-Z0-9_\x7f-\xff]',      ";",
                                    'variable', '\$', '[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*',
                                   ),
                         'typemap' => array(
                                       'c' => 'class',
                                       'f' => 'function',
                                      ),
                         'langid' => '9',
                        ),
               'Make' => array(
                          'reserved' => array(),
                          'spec' => array('comment',    '#',        "\$",
                                     'string',  '"',        '"',
                                     'string',  "'",        "'",
                                     'include', '^ *-?include', '\$'),
                          'typemap' => array(
                                        'm' => 'macro',
                                       ),
                          'langid' => '10',
                          ),
              )
);
