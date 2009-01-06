<?php
/**
 * @category   Horde
 * @package    Support
 * @copyright  2007-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */

/**
 * Horde Inflector class.
 *
 * @category   Horde
 * @package    Support
 * @copyright  2007-2009 The Horde Project (http://www.horde.org/)
 * @license    http://opensource.org/licenses/bsd-license.php
 */
class Horde_Support_Inflector {

    /**
     * Inflection cache
     * @var array
     */
    protected $_cache = array();

    /**
     * Rules for pluralizing English nouns.
     *
     * @var array
     */
    protected $_pluralizationRules = array(
        '/move$/i' => 'moves',
        '/sex$/i' => 'sexes',
        '/child$/i' => 'children',
        '/man$/i' => 'men',
        '/foot$/i' => 'feet',
        '/person$/i' => 'people',
        '/(quiz)$/i' => '$1zes',
        '/^(ox)$/i' => '$1en',
        '/(m|l)ouse$/i' => '$1ice',
        '/(matr|vert|ind)ix|ex$/i' => '$1ices',
        '/(x|ch|ss|sh)$/i' => '$1es',
        '/([^aeiouy]|qu)ies$/i' => '$1y',
        '/([^aeiouy]|qu)y$/i' => '$1ies',
        '/(?:([^f])fe|([lr])f)$/i' => '$1$2ves',
        '/sis$/i' => 'ses',
        '/([ti])um$/i' => '$1a',
        '/(buffal|tomat)o$/i' => '$1oes',
        '/(bu)s$/i' => '$1ses',
        '/(alias|status)$/i' => '$1es',
        '/(octop|vir)us$/i' => '$1i',
        '/(ax|test)is$/i' => '$1es',
        '/s$/i' => 's',
        '/$/' => 's',
    );

    /**
     * Rules for singularizing English nouns.
     *
     * @var array
     */
    protected $_singularizationRules = array(
        '/cookies$/i' => 'cookie',
        '/moves$/i' => 'move',
        '/sexes$/i' => 'sex',
        '/children$/i' => 'child',
        '/men$/i' => 'man',
        '/feet$/i' => 'foot',
        '/people$/i' => 'person',
        '/databases$/i'=> 'database',
        '/(quiz)zes$/i' => '\1',
        '/(matr)ices$/i' => '\1ix',
        '/(vert|ind)ices$/i' => '\1ex',
        '/^(ox)en/i' => '\1',
        '/(alias|status)es$/i' => '\1',
        '/([octop|vir])i$/i' => '\1us',
        '/(cris|ax|test)es$/i' => '\1is',
        '/(shoe)s$/i' => '\1',
        '/(o)es$/i' => '\1',
        '/(bus)es$/i' => '\1',
        '/([m|l])ice$/i' => '\1ouse',
        '/(x|ch|ss|sh)es$/i' => '\1',
        '/(m)ovies$/i' => '\1ovie',
        '/(s)eries$/i' => '\1eries',
        '/([^aeiouy]|qu)ies$/i' => '\1y',
        '/([lr])ves$/i' => '\1f',
        '/(tive)s$/i' => '\1',
        '/(hive)s$/i' => '\1',
        '/([^f])ves$/i' => '\1fe',
        '/(^analy)ses$/i' => '\1sis',
        '/((a)naly|(b)a|(d)iagno|(p)arenthe|(p)rogno|(s)ynop|(t)he)ses$/i' => '\1\2sis',
        '/([ti])a$/i' => '\1um',
        '/(n)ews$/i' => '\1ews',
        '/(.*)s$/i' => '\1',
    );

    /**
     * An array of words with the same singular and plural spellings.
     *
     * @var array
     */
    protected $_uncountables = array(
        'aircraft',
        'cannon',
        'deer',
        'equipment',
        'fish',
        'information',
        'money',
        'moose',
        'rice',
        'series',
        'sheep',
        'species',
        'swine',
    );

    /**
     * Constructor
     *
     * Store a map of the uncountable words for quicker checks.
     */
    public function __construct()
    {
        $this->_uncountables_keys = array_flip($this->_uncountables);
    }

    /**
     * Add an uncountable word.
     *
     * @param string $word The uncountable word.
     */
    public function uncountable($word)
    {
        $this->_uncountables[] = $word;
        $this->_uncountables_keys[$word] = true;
    }

    /**
     * Singular English word to pluralize.
     *
     * @param string $word Word to pluralize.
     *
     * @return string Plural form of $word.
     */
    public function pluralize($word)
    {
        if ($plural = $this->_getCache($word, 'pluralize')) {
            return $plural;
        }

        if (isset($this->_uncountables_keys[$word])) {
            return $word;
        }

        foreach ($this->_pluralizationRules as $regexp => $replacement) {
            $plural = preg_replace($regexp, $replacement, $word, -1, $matches);
            if ($matches > 0) {
                return $this->_cache($word, 'pluralize', $plural);
            }
        }

        return $this->_cache($word, 'pluralize', $word);
    }

    /**
     * Plural English word to singularize.
     *
     * @param string $word Word to singularize.
     *
     * @return string Singular form of $word.
     */
    public function singularize($word)
    {
        if ($singular = $this->_getCache($word, 'singularize')) {
            return $singular;
        }

        if (isset($this->_uncountables_keys[$word])) {
            return $word;
        }

        foreach ($this->_singularizationRules as $regexp => $replacement) {
            $singular = preg_replace($regexp, $replacement, $word, -1, $matches);
            if ($matches > 0) {
                return $this->_cache($word, 'singularize', $singular);
            }
        }

        return $this->_cache($word, 'singularize', $word);
    }

    /**
     * Camel-case a word
     *
     * @param string $word The word to camel-case
     * @param string $firstLetter Whether to upper or lower case the first
     * letter of each slash-separated section. Defaults to 'upper';
     *
     * @return string Camelized $word
     */
    public function camelize($word, $firstLetter = 'upper')
    {
        if ($camelized = $this->_getCache($word, 'camelize' . $firstLetter)) {
            return $camelized;
        }

        $camelized = $word;
        if (strtolower($camelized) != $camelized && strpos($camelized, '_') !== false) {
            $camelized = str_replace('_', '/', $camelized);
        }
        if (strpos($camelized, '/') !== false) {
            $camelized = str_replace('/', '/ ', $camelized);
        }
        if (strpos($camelized, '_') !== false) {
            $camelized = strtr($camelized, '_', ' ');
        }

        $camelized = str_replace(' ' , '', ucwords($camelized));

        if ($firstLetter == 'lower') {
            $parts = array();
            foreach (explode('/', $camelized) as $part) {
                $part[0] = strtolower($part[0]);
                $parts[] = $part;
            }
            $camelized = implode('/', $parts);
        }

        return $this->_cache($word, 'camelize' . $firstLetter, $camelized);
    }

    /**
     * Get a cached inflection
     *
     * @return string | false
     */
    protected function _getCache($word, $rule)
    {
        return isset($this->_cache[$word . '|' . $rule]) ?
            $this->_cache[$word . '|' . $rule] : false;
    }

    /**
     * Cache an inflection
     *
     * @param string $word The word being inflected
     * @param string $rule The inflection rule
     * @param string $value The inflected value of $word
     *
     * @return string The inflected value
     */
    protected function _cache($word, $rule, $value)
    {
        $this->_cache[$word . '|' . $rule] = $value;
        return $value;
    }

    /**
     * Clear the inflection cache
     */
    public function clearCache()
    {
        $this->_cache = array();
    }

}
