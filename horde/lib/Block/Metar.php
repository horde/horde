<?php
/**
 * An applet for the portal screen to display METAR weather data for a
 * specified location (currently airports).
 *
 * @package Horde
 */
class Horde_Block_Metar extends Horde_Core_Block
{
    /**
     */
    public $updateable = true;
    public $autoUpdateMethod = 'refreshContent';
    protected $_refreshParams;

    /**
     *
     * @var Horde_Service_Weather_Metar
     */
    protected $_weather;

    /**
     */
    public function __construct($app, $params = array())
    {
        global $injector, $conf;

        parent::__construct($app, $params);
        $this->_name = _("Metar Weather");
        $params = array(
            'cache' => $injector->getInstance('Horde_Cache'),
            'cache_lifetime' => $conf['weather']['params']['lifetime'],
            'http_client' => $injector->createInstance('Horde_Core_Factory_HttpClient')->create(),
            'db' => $injector->getInstance('Horde_Db_Adapter')
        );
        $this->_weather = new Horde_Service_Weather_Metar($params);
        $this->_weather->units = $this->_params['units'];
    }

    /**
     */
    protected function _title()
    {
        return _("Current Weather");
    }

    /**
     */
    protected function _params()
    {
        // @todo Find a way to allow not loading the entire metar location
        // database in memory. I.e., allow entering free-form text like
        // the other weather block.
        $rows = $this->_weather->getLocations();
        $locations = array();
        foreach ($rows as $row) {
            $locations[Horde_Nls_Translation::t($row['country'])][$row['icao']] = $row['name'];
        }
        uksort($locations, 'strcoll');

        return array(
            'location' => array(
                'type' => 'mlenum',
                'name' => _("Location"),
                'default' => 'KSFB',
                'values' => $locations,
            ),
            'units' => array(
                'type' => 'enum',
                'name' => _("Units"),
                'default' => Horde_Service_Weather::UNITS_STANDARD,
                'values' => array(
                    Horde_Service_Weather::UNITS_STANDARD => _("Standard"),
                    Horde_Service_Weather::UNITS_METRIC => _("Metric")
                )
            ),
            'knots' => array(
                'type' => 'checkbox',
                'name' => _("Wind speed in knots"),
                'default' => 0
            ),
            'taf' => array(
                'type' => 'checkbox',
                'name' => _("Display forecast (TAF)"),
                'default' => 0
            )
        );
    }

    /**
     * Handle user initiated block refresh. Set a private member to avoid
     * BC issues with having to add a parameter to the _content method.
     *
     * @param Horde_Variables $vars
     *
     * @return string
     */
    public function refreshContent($vars = null)
    {
        // if (empty($vars) || empty($vars->location)) {
        //     $this->_refreshParams = Horde_Variables::getDefaultVariables();
        //     $this->_refreshParams->set('location', $this->_params['location']);
        // } else {
        //     $this->_refreshParams = $vars;
        // }

        // return $this->_content();
    }

    /**
     */
    private function _row($label, $content)
    {
        return '<br /><strong>' . $label . ':</strong> ' . $content;
    }

    /**
     */
    private function _sameRow($label, $content)
    {
        return ' <strong>' . $label . ':</strong> ' . $content;
    }

    /**
     */
    protected function _content()
    {
        global $conf, $injector;
        static $metarLocs;

        if (empty($this->_params['location'])) {
            return _("No location is set.");
        }

        // @todo - refactor this out.
        if (!is_array($metarLocs)) {
            $metarLocs = $this->getParams();
        }

        // Get the data.
        $weather = $this->_weather->getCurrentConditions($this->_params['location'])->getRawData();
        $units = $this->_weather->getUnits();

        // Get the view object.
        $view = $injector->getInstance('Horde_View');
        $view->weather = $weather;
        $view->units = $units;

        // @todo - Use the station object.
        $view->location_title = sprintf('%s, %s (%s)',
            $metarLocs['location']['values'][$this->_params['__location']][$this->_params['location']],
            $this->_params['__location'],
            $this->_params['location']
        );

        // Wind.
        if (isset($weather['wind'])) {
            if ($weather['windDirection'] == 'Variable') {
                if (!empty($this->_params['knots'])) {
                    $view->wind = sprintf(_("%s at %s %s"),
                        $weather['windDirection'],
                        round(Horde_Service_Weather::convertSpeed($weather['wind'], $units['wind'], 'kt')),
                        'kt'
                    );
                } else {
                    $view->wind = sprintf(_("%s at %s %s"),
                        $weather['windDirection'],
                        round($weather['wind']),
                        $units['wind']
                    );
                }
            } elseif (($weather['windDegrees'] == '000') && ($weather['wind'] == '0')) {
                $view->wind = _("Calm");
            } else {
                $view->wind = sprintf(_("from the %s (%s) at %s %s"),
                    $weather['windDirection'],
                    $weather['windDegrees'],
                    empty($this->_params['knots'])
                        ? round($weather['wind'])
                        : round(Horde_Service_Weather::convertSpeed($weather['wind'], $units['wind'], 'kt')),
                    empty($this->_params['knots'])
                        ? $units['wind']
                        : 'kt'
                );
            }
        }

        // Gusts
        if (isset($weather['windGust'])) {
            if ($weather['windGust']) {
                if (!empty($this->_params['knots'])) {
                    $view->wind .= sprintf(_(", gusting %s %s"),
                        round(
                            Horde_Service_Weather::convertSpeed(
                                $weather['windGust'],
                                $units['wind'],
                                'kt'
                            )
                        ),
                        'kt'
                    );
                } else {
                    $view->wind .= sprintf(_(", gusting %s %s"),
                        round($weather['windGust']),
                        $units['wind']
                    );
                }
            }
        }

        // Variability
        if (isset($weather['windVariability'])) {
            if ($weather['windVariability']['from']) {
                $view->wind .= sprintf(_(", variable from %s to %s"),
                    $weather['windVariability']['from'],
                    $weather['windVariability']['to']
                );
            }
        }

        // Clouds.
        // @todo - fix units, fix indentation
        if (isset($weather['clouds'])) {
            $view->clouds = '';
            foreach ($weather['clouds'] as $cloud) {
                if (!empty($view->clouds)) {
                    $view->clouds .= '<br />  ';
                }
                if (isset($cloud['height'])) {
                    $view->clouds .= sprintf(
                        _("%s at %s %s"),
                        $cloud['amount'],
                        $cloud['height'],
                        $units['height']
                    );
                } else {
                    $view->clouds .= $cloud['amount'];
                }
            }
        }

        // Remarks.
        if (isset($weather['remark'])) {
            $view->remarks = '';
            $view->other = '';
            foreach ($weather['remark'] as $remark => $value) {
                switch ($remark) {
                case 'seapressure':
                    $view->remarks .= '<br />'
                        . _("Pressure at sea level: ")
                        . $value . ' ' . $units['pres'];
                    break;
                case 'precipitation':
                    foreach ($value as $precip) {
                        if (is_numeric($precip['amount'])) {
                            $view->remarks .= '<br />'
                                . sprintf(
                                    ngettext("Precipitation for last %d hour: ", "Precipitation for last %d hours: ", $precip['hours']),
                                    $precip['hours'])
                                . $precip['amount'] . ' ' . $units['rain'];
                        } else {
                            $view->remarks .= '<br />'
                                . sprintf(
                                    ngettext("Precipitation for last %d hour: ", "Precipitation for last %d hours: ", $precip['hours']),
                                    $precip['hours'])
                                . $precip['amount'];
                        }
                    }
                    break;
                case 'snowdepth':
                    $view->remarks .= '<br />'
                        . _("Snow depth: ") . $value . ' ' . $units['rain'];
                    break;
                case 'snowequiv':
                    $view->remarks .= '<br />'
                        . _("Snow equivalent in water: ")
                        . $value . ' ' . $units['rain'];
                    break;
                case 'sunduration':
                    $view->remarks .= '<br />'
                        . sprintf(_("%d minutes"), $value);
                    break;
                case '1htemp':
                    $view->remarks .= '<br />'
                        . _("Temp for last hour: ")
                        . round($value) . '&deg;'
                        . Horde_String::upper($units['temp']);
                    break;
                case '1hdew':
                    $view->remarks .= '<br />'
                        . _("Dew Point for last hour: ")
                        . round($value) . '&deg;'
                        . Horde_String::upper($units['temp']);
                    break;
                case '6hmaxtemp':
                    $view->remarks .= '<br />'
                        . _("Max temp last 6 hours: ")
                        . round($value) . '&deg;'
                        . Horde_String::upper($units['temp']);
                    break;
                case '6hmintemp':
                    $view->remarks .= '<br />'
                        . _("Min temp last 6 hours: ")
                            . round($value) . '&deg;'
                            . Horde_String::upper($units['temp']);
                    break;
                case '24hmaxtemp':
                    $view->remarks .= '<br />'
                        . _("Max temp last 24 hours: ")
                        . round($value) . '&deg;'
                        . Horde_String::upper($units['temp']);
                    break;
                case '24hmintemp':
                    $view->remarks .= '<br />'
                        . _("Min temp last 24 hours: ")
                        . round($value) . '&deg;'
                        . Horde_String::upper($units['temp']);
                    break;
                case 'sensors':
                    foreach ($value as $sensor) {
                        $view->remarks .= '<br />'
                            . _("Sensor: ") . $sensor;
                    }
                    break;
                default:
                    $view->other .= '<br />' . $value;
                    break;
                }
            }
        }

        // TAF
        if (!empty($this->_params['taf'])) {
            $taf = $this->_weather->getForecast($this->_params['location'])->getRawData();
            $view->item = 0;
            $view->periods = array();
            foreach ($taf['time'] as $time => $entry) {
                $period = array('time' => $time);
                // Wind
                if (isset($entry['wind'])) {
                    if ($entry['windDirection'] == 'Variable') {
                        if (!empty($this->_params['knots'])) {
                            $period['wind'] = sprintf(
                                _("%s at %s %s"),
                                strtolower($entry['windDirection']),
                                round(Horde_Service_Weather::convertSpeed(
                                    $entry['wind'],
                                    $units['wind'],
                                    'kt')),
                                'kt'
                            );
                        } else {
                            $period['wind'] = sprintf(
                                _("%s at %s %s"),
                                $entry['windDirection'],
                                round($entry['wind']),
                                $units['wind']
                            );
                        }
                    } elseif (($entry['windDegrees'] == '000') && ($entry['wind'] == '0')) {
                        $period['wind'] = _("Calm");
                    } else {
                        $period['wind'] = sprintf(
                            _("from the %s (%s) at %s %s"),
                            $entry['windDirection'],
                            $entry['windDegrees'],
                            empty($this->_params['knots'])
                                ? round($entry['wind'])
                                : round(Horde_Service_Weather::convertSpeed($entry['wind'], $units['wind'], 'kt')),
                            empty($this->_params['knots'])
                                ? $units['wind']
                                : 'kt'
                        );
                    }
                }

                // Temp
                if (isset($entry['temperatureLow']) ||
                    isset($entry['temperatureHigh'])) {
                    if (isset($entry['temperatureLow'])) {
                        $period['temperatureLow'] = $entry['temperatureLow'];
                    }
                    if (isset($entry['temperatureHigh'])) {
                        $period['temperatureHigh'] =  $entry['temperatureHigh'];
                    }
                }

                // Wind Shear
                if (isset($entry['windshear'])) {
                    $period['shear'] = sprintf(
                        _("from the %s (%s) at %s %s"),
                        $entry['windshearDirection'],
                        $entry['windshearDegrees'],
                        $entry['windshearHeight'],
                        $units['height']
                    );
                }

                // Visibility
                if (isset($entry['visibility'])) {
                    $period['visibility'] = strtolower($entry['visQualifier']) . ' ' . $entry['visibility'] . ' ' . $units['vis'];
                }

                // Conditions
                if (isset($entry['condition'])) {
                    $period['condition'] = $entry['condition'];
                }
                // Clouds
                $period['clouds'] = $entry['clouds'];

                // FMC
                if (isset($entry['fmc'])) {
                    $period['fmc'] = $entry['fmc'];
                    $period['fmc']['clouds'] = !empty($period['fmc']['clouds'])
                        ? $period['fmc']['clouds']
                        : array();
                }

                // Set the period in the view.
                $view->periods[] = $period;
            }
        }

        return $view->render('block/metar_content');
    }

}
