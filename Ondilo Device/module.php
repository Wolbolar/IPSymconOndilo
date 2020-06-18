<?php
declare(strict_types=1);

require_once(__DIR__ . "/../bootstrap.php");
require_once __DIR__ . '/../libs/ProfileHelper.php';
require_once __DIR__ . '/../libs/ConstHelper.php';

class OndiloDevice extends IPSModule
{
    use ProfileHelper;

    // helper properties
    private const MICRO_SIEMENS_PER_CENTI_METER = 'MICRO_SIEMENS_PER_CENTI_METER';
    private const FRENCH_DEGREE = 'FRENCH_DEGREE';
    private const MILLI_VOLT = 'MILLI_VOLT';
    private const HECTO_PASCAL = 'HECTO_PASCAL';
    private const GRAM_PER_LITER = 'GRAM_PER_LITER';
    private const METER_PER_SECOND = 'METER_PER_SECOND';
    private const CELSIUS = 'CELSIUS';
    private const CUBIC_METER = 'CUBIC_METER';
    private $position = 0;

    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->ConnectParent('{703B7E3E-5531-71FA-5905-AE11110DDD7E}');

        $this->RegisterPropertyString('id', '');
        $this->RegisterPropertyString('name', '');
        $this->RegisterAttributeBoolean('name_enabled', false);
        $this->RegisterAttributeString('uuid', '');
        $this->RegisterAttributeBoolean('uuid_enabled', false);
        $this->RegisterAttributeString('serial_number', '');
        $this->RegisterAttributeBoolean('serial_number_enabled', false);
        $this->RegisterAttributeString('sw_version', '');
        $this->RegisterAttributeBoolean('sw_version_enabled', false);
        $this->RegisterAttributeString('user_lastname', '');
        $this->RegisterAttributeString('user_firstname', '');
        $this->RegisterAttributeString('user_email', '');
        $this->RegisterAttributeString('user_units', '[]');
        $this->RegisterAttributeString('unit_conductivity', '');
        $this->RegisterAttributeString('unit_hardness', '');
        $this->RegisterAttributeString('unit_orp', '');
        $this->RegisterAttributeString('unit_pressure', '');
        $this->RegisterAttributeString('unit_salt', '');
        $this->RegisterAttributeString('unit_speed', '');
        $this->RegisterAttributeString('unit_temperature', '');
        $this->RegisterAttributeString('unit_volume', '');
        $this->RegisterAttributeInteger('temperature_low', 0);
        $this->RegisterAttributeInteger('temperature_high', 0);
        $this->RegisterAttributeFloat('ph_low', 0);
        $this->RegisterAttributeFloat('ph_high', 0);
        $this->RegisterAttributeInteger('orp_low', 0);
        $this->RegisterAttributeInteger('orp_high', 0);
        $this->RegisterAttributeInteger('salt_low', 0);
        $this->RegisterAttributeInteger('salt_high', 0);
        $this->RegisterAttributeInteger('tds_low', 0);
        $this->RegisterAttributeInteger('tds_high', 0);
        $this->RegisterAttributeInteger('pool_guy_number', 0);
        $this->RegisterAttributeInteger('maintenance_day', 0);
        $this->RegisterAttributeString('pool_shares', '[]');
        $this->RegisterAttributeInteger('last_measure', 0);
        $this->RegisterAttributeBoolean('last_measure_enabled', false);
        $this->RegisterAttributeFloat('temperature', 0);
        $this->RegisterAttributeBoolean('temperature_enabled', false);
        $this->RegisterAttributeBoolean('temperature_is_valid', true);
        $this->RegisterAttributeBoolean('temperature_is_valid_enabled', false);
        $this->RegisterAttributeInteger('orp', 0);
        $this->RegisterAttributeBoolean('orp_enabled', false);
        $this->RegisterAttributeBoolean('orp_is_valid', true);
        $this->RegisterAttributeBoolean('orp_is_valid_enabled', false);
        $this->RegisterAttributeInteger('tds', 0);
        $this->RegisterAttributeBoolean('tds_enabled', false);
        $this->RegisterAttributeBoolean('tds_is_valid', true);
        $this->RegisterAttributeBoolean('tds_is_valid_enabled', false);
        $this->RegisterAttributeFloat('ph', 0);
        $this->RegisterAttributeBoolean('ph_enabled', false);
        $this->RegisterAttributeBoolean('ph_is_valid', true);
        $this->RegisterAttributeBoolean('ph_is_valid_enabled', false);
        $this->RegisterAttributeInteger('battery', 0);
        $this->RegisterAttributeBoolean('battery_enabled', false);
        $this->RegisterAttributeBoolean('battery_is_valid', true);
        $this->RegisterAttributeBoolean('battery_is_valid_enabled', false);
        $this->RegisterAttributeInteger('rssi', 0);
        $this->RegisterAttributeBoolean('rssi_enabled', false);
        $this->RegisterAttributeBoolean('rssi_is_valid', true);
        $this->RegisterAttributeBoolean('rssi_is_valid_enabled', false);

        //we will wait until the kernel is ready
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        switch ($Message) {
            case IM_CHANGESTATUS:
                if ($Data[0] === IS_ACTIVE) {
                    $this->ApplyChanges();
                }
                break;

            case IPS_KERNELMESSAGE:
                if ($Data[0] === KR_READY) {
                    $this->ApplyChanges();
                }
                break;

            default:
                break;
        }
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }
        $id = $this->ReadPropertyString('id');
        $this->SetReceiveDataFilter(".*" . $id . ".*");
        $this->ValidateConfiguration();
    }

    private function ValidateConfiguration()
    {
        $id = $this->ReadPropertyString('id');
        if ($id == '') {
            $this->SetStatus(205);
        } elseif ($id != '') {
            $this->RegisterVariables();
            $this->SetStatus(IS_ACTIVE);
        }
    }


    /** @noinspection PhpMissingParentCallCommonInspection */

    private function RegisterVariables(): void
    {

        $valid_ass = [
            [true, $this->Translate("valid"), "", -1],
            [false, $this->Translate("not valid"), "", -1]];
        $this->RegisterProfileAssociation('Ondilo.Valid', 'Network', '', '', 0, 1, 0, 0, VARIABLETYPE_BOOLEAN, $valid_ass);

        $this->SetupVariable(
            'name', $this->Translate('name'), '', $this->_getPosition(), VARIABLETYPE_STRING, false, false
        );
        $this->SetupVariable(
            'serial_number', $this->Translate('serial number'), '', $this->_getPosition(), VARIABLETYPE_STRING, false, false
        );
        $temperature_ass = [
            [0.0, $this->Translate("cold"). ' %.1f', "", 255],
            [15.0, '%.1f', "", 65280],
            [28.0, $this->Translate("hot"). ' %.1f', "", 16711680]];
        $this->RegisterProfileAssociation('Ondilo.Temperature', 'Temperature', '', ' °C', 0, 50, 1, 1, VARIABLETYPE_FLOAT, $temperature_ass);
        // ~Temperature
        $this->SetupVariable(
            'temperature', $this->Translate('temperature'), 'Ondilo.Temperature', $this->_getPosition(), VARIABLETYPE_FLOAT, false, true
        );
        $this->SetupVariable(
            'temperature_is_valid', $this->Translate('temperature is valid'), 'Ondilo.Valid', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        );
        $orp_ass = [
            [0, $this->Translate("low"). ' %d', "", 255],
            [650, '%d', "", 65280],
            [750, $this->Translate("high"). ' %d', "", 16711680]];
        $this->RegisterProfileAssociation('Ondilo.ORP', 'DoctorBag', '', ' mV', 0, 5000, 10, 0, VARIABLETYPE_INTEGER, $orp_ass);

        // $this->RegisterProfile('Ondilo.ORP', 'DoctorBag', '', ' mV', 0, 5000, 10, 0, VARIABLETYPE_INTEGER);
        $this->SetupVariable(
            'orp', $this->Translate('redox potential'), 'Ondilo.ORP', $this->_getPosition(), VARIABLETYPE_INTEGER, false, true
        );
        $this->SetupVariable(
            'orp_is_valid', $this->Translate('orp is valid'), 'Ondilo.Valid', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        );
        $tds_ass = [
            [0, $this->Translate("low"). ' %d', "", 255],
            [250, '%d', "", 65280],
            [2000, $this->Translate("high") . ' %d', "", 16711680]];
        $this->RegisterProfileAssociation('Ondilo.TDS', 'Snow', '', ' ppm', 0, 5000, 10, 0, VARIABLETYPE_INTEGER, $tds_ass);
        $this->SetupVariable(
            'tds', $this->Translate('tds'), 'Ondilo.TDS', $this->_getPosition(), VARIABLETYPE_INTEGER, false, true
        );
        $this->SetupVariable(
            'tds_is_valid', $this->Translate('tds is valid'), 'Ondilo.Valid', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        );
        $ph_ass = [
            [0, $this->Translate("acidic"). ' %.2f', "", 16711680],
            [6.8, '%.2f', "", 65280],
            [7.4, $this->Translate("alkaline"). ' %.2f', "", 255]];
        $this->RegisterProfileAssociation('Ondilo.pH', 'Gauge', '', '', 0, 14, 1, 1, VARIABLETYPE_FLOAT, $ph_ass);
        $this->SetupVariable(
            'ph', $this->Translate('pH'), 'Ondilo.pH', $this->_getPosition(), VARIABLETYPE_FLOAT, false, true
        );
        $this->SetupVariable(
            'ph_is_valid', $this->Translate('pH is valid'), 'Ondilo.Valid', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        );
        $this->SetupVariable(
            'uuid', $this->Translate('uuid'), '', $this->_getPosition(), VARIABLETYPE_STRING, false, false
        );
        $this->SetupVariable(
            'sw_version', $this->Translate('software version'), '', $this->_getPosition(), VARIABLETYPE_STRING, false, false
        );
        $this->SetupVariable(
            'battery', $this->Translate('battery'), '~Battery.100', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'battery_is_valid', $this->Translate('battery is valid'), 'Ondilo.Valid', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        );
        $last_measure_exist = true;
        $obj_lastmeasure = @$this->GetIDForIdent('last_measure');
        if($obj_lastmeasure == false)
        {
            $last_measure_exist = false;
        }
        $objid = $this->SetupVariable(
            'last_measure', $this->Translate('last measure'), '~UnixTimestamp', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        if($last_measure_exist == false)
        {
            IPS_SetIcon($objid, 'Clock');
        }

        $this->SetupVariable(
            'rssi', $this->Translate('rssi'), '~Intensity.100', $this->_getPosition(), VARIABLETYPE_INTEGER, false, false
        );
        $this->SetupVariable(
            'rssi_is_valid', $this->Translate('rssi is valid'), 'Ondilo.Valid', $this->_getPosition(), VARIABLETYPE_BOOLEAN, false, false
        );

        // $this->GetDeviceStatus();

        $this->WriteValues();
    }

    /** Variable anlegen / löschen
     *
     * @param $ident
     * @param $name
     * @param $profile
     * @param $position
     * @param $vartype
     * @param $visible
     *
     * @return bool|int
     */
    protected function SetupVariable($ident, $name, $profile, $position, $vartype, $enableaction, $visible = false)
    {
        $objid = false;
        if ($visible) {
            $this->SendDebug('Ondilo Variable:', 'Variable with Ident ' . $ident . ' is visible', 0);
        } else {
            $visible = $this->ReadAttributeBoolean($ident . '_enabled');
            $this->SendDebug('Ondilo Variable:', 'Variable with Ident ' . $ident . ' is shown ' . print_r($visible, true), 0);
        }
        if ($visible == true) {
            switch ($vartype) {
                case VARIABLETYPE_BOOLEAN:
                    $objid = $this->RegisterVariableBoolean($ident, $name, $profile, $position);
                    $value = $this->ReadAttributeBoolean($ident);
                    break;
                case VARIABLETYPE_INTEGER:
                    $objid = $this->RegisterVariableInteger($ident, $name, $profile, $position);
                    $value = $this->ReadAttributeInteger($ident);
                    break;
                case VARIABLETYPE_FLOAT:
                    $objid = $this->RegisterVariableFloat($ident, $name, $profile, $position);
                    $value = $this->ReadAttributeFloat($ident);
                    break;
                case VARIABLETYPE_STRING:
                    $objid = $this->RegisterVariableString($ident, $name, $profile, $position);
                    if ($ident == 'name') {
                        $value = $this->ReadPropertyString($ident);
                    } else {
                        $value = $this->ReadAttributeString($ident);
                    }
                    break;
            }
            $this->SetValue($ident, $value);
            if ($enableaction) {
                $this->EnableAction($ident);
            }
        } else {
            $objid = @$this->GetIDForIdent($ident);
            if ($objid > 0) {
                $this->UnregisterVariable($ident);
            }
        }
        return $objid;
    }

    /**
     * return incremented position
     * @return int
     */
    private function _getPosition()
    {
        $this->position++;
        return $this->position;
    }


    /** @noinspection PhpMissingParentCallCommonInspection */

    private function WriteValues()
    {
        $id = $this->ReadPropertyString('id');
        $this->SendDebug('Ondilo Write Values', 'Pool ID ' . $id, 0);
        $this->WriteEnabledValue('temperature', VARIABLETYPE_FLOAT, true);
        $this->WriteEnabledValue('last_measure', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('temperature_is_valid', VARIABLETYPE_BOOLEAN);
        $this->WriteEnabledValue('orp', VARIABLETYPE_INTEGER, true);
        $this->WriteEnabledValue('orp_is_valid', VARIABLETYPE_BOOLEAN);
        $this->WriteEnabledValue('tds', VARIABLETYPE_INTEGER, true);
        $this->WriteEnabledValue('tds_is_valid', VARIABLETYPE_BOOLEAN);
        $this->WriteEnabledValue('ph', VARIABLETYPE_FLOAT, true);
        $this->WriteEnabledValue('ph_is_valid', VARIABLETYPE_BOOLEAN);
        $this->WriteEnabledValue('battery', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('battery_is_valid', VARIABLETYPE_BOOLEAN);
        $this->WriteEnabledValue('rssi', VARIABLETYPE_INTEGER);
        $this->WriteEnabledValue('rssi_is_valid', VARIABLETYPE_BOOLEAN);
    }

    // Ondilo API

    // User data

    private function WriteEnabledValue($ident, $vartype, $enabled = false)
    {
        if ($enabled) {
            $value_enabled = true;
        } else {
            $value_enabled = $this->ReadAttributeBoolean($ident . '_enabled');
        }

        if ($value_enabled) {
            switch ($vartype) {
                case VARIABLETYPE_BOOLEAN:
                    $value = $this->ReadAttributeBoolean($ident);
                    $this->SendDebug('SetValue boolean', 'ident: ' . $ident . ' value: ' . $value, 0);
                    $this->SetVariableValue($ident, $value);
                    break;
                case VARIABLETYPE_INTEGER:
                    $value = $this->ReadAttributeInteger($ident);
                    $this->SendDebug('SetValue integer', 'ident: ' . $ident . ' value: ' . $value, 0);
                    $this->SetVariableValue($ident, $value);
                    break;
                case VARIABLETYPE_FLOAT:
                    $value = $this->ReadAttributeFloat($ident);
                    $this->SendDebug('SetValue float', 'ident: ' . $ident . ' value: ' . $value, 0);
                    $this->SetVariableValue($ident, $value);
                    break;
                case VARIABLETYPE_STRING:
                    $value = $this->ReadAttributeString($ident);
                    $this->SendDebug('SetValue string', 'ident: ' . $ident . ' value: ' . $value, 0);
                    $this->SetVariableValue($ident, $value);
                    break;
            }
        }
    }

    private function SetVariableValue($ident, $value)
    {
        if (@$this->GetIDForIdent($ident)) {
            $this->SetValue($ident, $value);
        }
    }

    public function RequestAction($Ident, $Value)
    {
        /*
        if ($Ident === 'xx') {
            $this->Do($Value, 1);
        }
        */
    }

    public function ReadPoolInformation()
    {
        $this->GetUserInformation();
        $this->GetUserUnits();
        $this->GetPoolConfiguration();
        $this->GetPoolDevice();
        $this->GetPoolShares();
        $this->GetLastMeasure();
    }

    /** User information
     * @return string
     */
    public function GetUserInformation()
    {
        $user_info_json = $this->RequestStatus('GetUserInformation');
        $user_info = json_decode($user_info_json);
        if ($user_info != false) {
            $user_lastname = $user_info->lastname;
            $this->WriteAttributeString('user_lastname', $user_lastname);
            $user_firstname = $user_info->firstname;
            $this->WriteAttributeString('user_firstname', $user_firstname);
            $user_email = $user_info->email;
            $this->WriteAttributeString('user_email', $user_email);
        }
        return $user_info;
    }

    private function RequestStatus(string $endpoint)
    {
        $id = $this->ReadPropertyString('id');
        $data = $this->SendDataToParent(json_encode([
            'DataID' => '{13683E92-8B41-A54D-BFE4-6496AAFC7FF5}',
            'Type' => 'GET',
            'Endpoint' => $endpoint,
            'id' => $id,
            'Payload' => ''
        ]));
        $this->SendDebug('Ondilo Request Response', $data, 0);
        return $data;
    }

    /** User Units
     * @return string
     */
    public function GetUserUnits()
    {
        $user_units_json = $this->RequestStatus('GetUserUnits');
        $user_units = json_decode($user_units_json);
        if ($user_units != false) {
            $conductivity = $user_units->conductivity;
            $this->SendDebug('Ondilo conductivity', $conductivity, 0);
            $this->WriteAttributeString('unit_conductivity', $conductivity);
            $hardness = $user_units->hardness;
            $this->SendDebug('Ondilo hardness', $hardness, 0);
            $this->WriteAttributeString('unit_hardness', $hardness);
            $orp = $user_units->orp;
            $this->SendDebug('Ondilo orp', $orp, 0);
            $this->WriteAttributeString('unit_orp', $orp);
            $pressure = $user_units->pressure;
            $this->SendDebug('Ondilo pressure', $pressure, 0);
            $this->WriteAttributeString('unit_pressure', $pressure);
            $salt = $user_units->salt;
            $this->SendDebug('Ondilo salt', $salt, 0);
            $this->WriteAttributeString('unit_salt', $salt);
            $speed = $user_units->speed;
            $this->SendDebug('Ondilo speed', $speed, 0);
            $this->WriteAttributeString('unit_speed', $speed);
            $temperature = $user_units->temperature;
            $this->SendDebug('Ondilo temperature', $temperature, 0);
            $this->WriteAttributeString('unit_temperature', $temperature);
            $volume = $user_units->volume;
            $this->SendDebug('Ondilo volume', $volume, 0);
            $this->WriteAttributeString('unit_volume', $volume);
            $this->WriteAttributeString('user_units', json_encode($user_units));
        }
        return $user_units;
    }

    /** Pool/spa configuration
     * @return string
     */
    public function GetPoolConfiguration()
    {
        $pool_configuration_json = $this->RequestStatus('GetPoolConfiguration');
        $pool_configuration = json_decode($pool_configuration_json);
        if ($pool_configuration != false) {
            $temperature_low = $pool_configuration->temperature_low;
            $this->SendDebug('Ondilo temperature low', $temperature_low, 0);
            $this->WriteAttributeInteger('temperature_low', $temperature_low);
            $temperature_high = $pool_configuration->temperature_high;
            $this->SendDebug('Ondilo temperature high', $temperature_high, 0);
            $this->WriteAttributeInteger('temperature_high', $temperature_high);
            $ph_low = $pool_configuration->ph_low;
            $this->SendDebug('Ondilo ph low', $ph_low, 0);
            $this->WriteAttributeFloat('ph_low', $ph_low);
            $ph_high = $pool_configuration->ph_high;
            $this->SendDebug('Ondilo ph high', $ph_high, 0);
            $this->WriteAttributeFloat('ph_high', $ph_high);
            $orp_low = $pool_configuration->orp_low;
            $this->SendDebug('Ondilo orp low', $orp_low, 0);
            $this->WriteAttributeInteger('orp_low', $orp_low);
            $orp_high = $pool_configuration->orp_high;
            $this->SendDebug('Ondilo orp high', $orp_high, 0);
            $this->WriteAttributeInteger('orp_high', $orp_high);
            $salt_low = $pool_configuration->salt_low;
            $this->SendDebug('Ondilo salt low', $salt_low, 0);
            $this->WriteAttributeInteger('salt_low', $salt_low);
            $salt_high = $pool_configuration->salt_high;
            $this->SendDebug('Ondilo salt high', $salt_high, 0);
            $this->WriteAttributeInteger('salt_high', $salt_high);
            $tds_low = $pool_configuration->tds_low;
            $this->SendDebug('Ondilo tds low', $tds_low, 0);
            $this->WriteAttributeInteger('tds_low', $tds_low);
            $tds_high = $pool_configuration->tds_high;
            $this->SendDebug('Ondilo tds high', $tds_high, 0);
            $this->WriteAttributeInteger('tds_high', $tds_high);
            $pool_guy_number = $pool_configuration->pool_guy_number;
            $this->SendDebug('Ondilo pool guy number', $pool_guy_number, 0);
            $this->WriteAttributeInteger('pool_guy_number', $pool_guy_number);
            $maintenance_day = $pool_configuration->maintenance_day;
            $this->SendDebug('Ondilo maintenance_day', $maintenance_day, 0);
            $this->WriteAttributeInteger('maintenance_day', $maintenance_day);
        }
        return $pool_configuration_json;
    }

    /** Pool/spa device
     * @return string
     */
    public function GetPoolDevice()
    {
        $pool_device_json = $this->RequestStatus('GetPoolDevice');
        $pool_device = json_decode($pool_device_json);
        if ($pool_device != false) {
            $uuid = $pool_device->uuid;
            $this->SendDebug('Ondilo Pool uuid', $uuid, 0);
            $this->WriteAttributeString('uuid', $uuid);
            $serial_number = $pool_device->serial_number;
            $this->SendDebug('Ondilo ICO serial_number', $serial_number, 0);
            $this->WriteAttributeString('serial_number', $serial_number);
            $sw_version = $pool_device->sw_version;
            $this->SendDebug('Ondilo software version', $sw_version, 0);
            $this->WriteAttributeString('sw_version', $sw_version);
        }
        return $pool_device_json;
    }

    /** Pool/spa shares
     * @return string
     */
    public function GetPoolShares()
    {
        $pool_shares_json = $this->RequestStatus('GetPoolShares');
        $pool_shares = json_decode($pool_shares_json);
        if ($pool_shares != false) {
            foreach ($pool_shares as $key => $pool) {
                $lastname = $pool_shares->lastname;
                $this->SendDebug('Ondilo share lastname', $lastname, 0);
                $firstname = $pool_shares->firstname;
                $this->SendDebug('Ondilo share firstname', $firstname, 0);
                $email = $pool_shares->email;
                $this->SendDebug('Ondilo share email', $email, 0);
                $shared_since = $pool_shares->shared_since;
                $this->SendDebug('Ondilo shared since', $shared_since, 0);
            }
            $this->WriteAttributeString('pool_shares', json_encode($pool_shares));
        }
        return $pool_shares;
    }

    /** Last measure
     * @return string
     */
    public function GetLastMeasure()
    {
        $last_measure_json = $this->RequestStatus('GetLastMeasure');
        $this->SendDebug('Receive ICO data', $last_measure_json, 0);
        $last_measure = json_decode($last_measure_json);
        $this->FetchLastMeasure($last_measure);
        return $last_measure;
    }

    private function FetchLastMeasure($last_measures)
    {
        foreach ($last_measures as $key => $last_measure) {
            $data_type = $last_measure->data_type;
            $value = $last_measure->value;
            $value_time = $last_measure->value_time;
            $is_valid = $last_measure->is_valid;
            // $exclusion_reason = $last_measure->exclusion_reason;
            if ($data_type == 'temperature') {
                $this->WriteAttributeFloat('temperature', $value);
                $this->WriteAttributeInteger('last_measure', $this->CalculateTime($value_time, 'last measure'));
                $this->WriteAttributeBoolean('temperature_is_valid', $is_valid);
            }
            if ($data_type == 'orp') {
                $this->WriteAttributeInteger('orp', $value);
                $this->WriteAttributeBoolean('orp_is_valid', $is_valid);
            }
            if ($data_type == 'tds') {
                $this->WriteAttributeInteger('tds', $value);
                $this->WriteAttributeBoolean('tds_is_valid', $is_valid);
            }
            if ($data_type == 'ph') {
                $this->WriteAttributeFloat('ph', $value);
                $this->WriteAttributeBoolean('ph_is_valid', $is_valid);
            }
            if ($data_type == 'battery') {
                $this->WriteAttributeInteger('battery', $value);
                $this->WriteAttributeBoolean('battery_is_valid', $is_valid);
            }
            if ($data_type == 'rssi') {
                $this->WriteAttributeInteger('rssi', $value);
                $this->WriteAttributeBoolean('rssi_is_valid', $is_valid);
            }
            $this->SendDebug('Ondilo data', $data_type . ': ' . $value, 0);
        }
        $this->WriteValues();
    }

    private function CalculateTime($time_string, $subject)
    {
        $date = new DateTime($time_string);
        $date->setTimezone(new DateTimeZone('Europe/Berlin'));
        $timestamp = $date->getTimestamp();
        $this->SendDebug('Ondilo ' . $subject . ' Timestamp', $date->format('Y-m-d H:i:sP'), 0);
        return $timestamp;
    }

    /** Set of measures
     * @return string
     */
    public function GetSetOfMeasures()
    {
        $last_measures_json = $this->RequestStatus('GetSetOfMeasures');
        $this->SendDebug('Receive ICO data', $last_measures_json, 0);
        $last_measures = json_decode($last_measures_json);
        // $this->FetchLastMeasure($last_measures);
        return $last_measures;
    }

    /** List active recommendations
     * @return string
     */
    public function GetListActiveRecommendations()
    {
        $recomendations = $this->RequestStatus('GetListActiveRecommendations');
        return $recomendations;
    }

    /** Validate recommendation
     * @return string
     */
    public function ValidateRecommendation(string $recommendation_id)
    {
        $id = $this->ReadPropertyString('id');
        $result = $this->SendDataToParent(json_encode([
            'DataID' => '{13683E92-8B41-A54D-BFE4-6496AAFC7FF5}',
            'Type' => 'PUT',
            'Endpoint' => '/api/customer/v1/pools/' . $id . '/recommendations/' . $recommendation_id,
            'id' => $id,
            'Payload' => ''
        ]));
        return $result;
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString);
        $payload = $data->Buffer;
        $this->SendDebug('Receive from I/O', json_encode($payload), 0);
        $this->CheckDeviceData($payload);
    }

    private function CheckDeviceData($payload)
    {
        if (!empty($payload)) {
            $pool_id = $payload->pool_id;
            $id = $this->ReadPropertyString('id');
            if($id == $pool_id)
            {
                $this->SendDebug('Receive ICO pool id', $pool_id, 0);
                $data = $payload->data;
                $this->SendDebug('Receive ICO data', json_encode($data), 0);
                $this->FetchLastMeasure($data);
            }
        }
    }

    public function SetWebFrontVariable(string $ident, bool $value)
    {
        $this->WriteAttributeBoolean($ident, $value);
        if ($value) {
            $this->SendDebug('Ondilo Webfront Variable', $ident . ' enabled', 0);
        } else {
            $this->SendDebug('Ondilo Webfront Variable', $ident . ' disabled', 0);
        }

        $this->RegisterVariables();
    }

    /***********************************************************
     * Configuration Form
     ***********************************************************/

    /**
     * build configuration form
     * @return string
     */
    public function GetConfigurationForm()
    {
        // return current form
        return json_encode([
            'elements' => $this->FormHead(),
            'actions' => $this->FormActions(),
            'status' => $this->FormStatus()
        ]);
    }

    /**
     * return form configurations on configuration step
     * @return array
     */
    protected function FormHead()
    {
        $data = $this->CheckRequest();
        $serial_number = $this->ReadAttributeString('serial_number');
        if ($serial_number == '') {
            $visibility_serial = false;
        } else {
            $visibility_serial = true;
        }

        if ($data != false) {
            $form = [
                [
                    'type' => 'RowLayout',
                    'visible' => true,
                    'items' => [
                        [
                            'type' => 'Image',
                            'image' => 'data:image/png;base64, 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',],

                        [
                            'type' => 'Label',
                            'label' => $this->ReadPropertyString('name')
                        ],]],
                [
                    'type' => 'Label',
                    'visible' => $visibility_serial,
                    'label' => $this->Translate('serial number: ') . $this->ReadAttributeString('serial_number')
                ],
                [
                    'type' => 'Label',
                    'visible' => $visibility_serial,
                    'label' => $this->Translate('software version') . ': '. $this->ReadAttributeString('sw_version')
                ],
                [
                    'type' => 'Label',
                    'visible' => $visibility_serial,
                    'label' => $this->Translate('uuid: ') . $this->ReadAttributeString('uuid')
                ],
                [
                    'type' => 'Label',
                    'visible' => false,
                    'label' => $this->Translate('pool id: ') . $this->Translate($this->ReadPropertyString('id'))
                ],
                [
                    'type' => 'RowLayout',
                    'visible' => true,
                    'items' => [
                        [
                            'type' => 'Label',
                            'label' => $this->Translate('Owner:')
                        ],
                        [
                            'type' => 'Label',
                            'label' => $this->ReadAttributeString('user_firstname') . ' ' . $this->ReadAttributeString('user_lastname')  . ', (' . $this->ReadAttributeString('user_email') . ')'
                        ],]],
            ];
        } else {
            $form = [
                [
                    'type' => 'Label',
                    'label' => 'This device can only created by the Ondilo configurator, please use the Ondilo configurator for creating Ondilo devices.'
                ]
            ];
        }
        return $form;
    }

    private function CheckRequest()
    {
        $id = $this->ReadPropertyString('id');
        $data = false;
        if ($id == '') {
            $this->SetStatus(205);
        } elseif ($id != '') {
            $data = $this->RequestStatus('GetUserInformation');
        }
        return $data;
    }

    /**
     * return form actions by token
     * @return array
     */
    protected function FormActions()
    {
        $form = [
            [
                'name' => 'name_enabled',
                'type' => 'CheckBox',
                'caption' => 'name',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('name_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "name_enabled", $name_enabled);'],
            [
                'name' => 'serial_number_enabled',
                'type' => 'CheckBox',
                'caption' => 'serial number',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('serial_number_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "serial_number_enabled", $serial_number_enabled);'],
            [
                'name' => 'last_measure_enabled',
                'type' => 'CheckBox',
                'caption' => 'last measure',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('last_measure_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "last_measure_enabled", $last_measure_enabled);'],
            [
                'name' => 'temperature_is_valid_enabled',
                'type' => 'CheckBox',
                'caption' => 'temperature is valid',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('temperature_is_valid_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "temperature_is_valid_enabled", $temperature_is_valid_enabled);'],
            [
                'name' => 'orp_is_valid_enabled',
                'type' => 'CheckBox',
                'caption' => 'orp is valid',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('orp_is_valid_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "orp_is_valid_enabled", $orp_is_valid_enabled);'],
            [
                'name' => 'tds_is_valid_enabled',
                'type' => 'CheckBox',
                'caption' => 'tds is valid',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('tds_is_valid_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "tds_is_valid_enabled", $tds_is_valid_enabled);'],
            [
                'name' => 'ph_is_valid_enabled',
                'type' => 'CheckBox',
                'caption' => 'pH is valid',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('ph_is_valid_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "ph_is_valid_enabled", $ph_is_valid_enabled);'],
            [
                'name' => 'battery_enabled',
                'type' => 'CheckBox',
                'caption' => 'battery',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('battery_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "battery_enabled", $battery_enabled);'],
            [
                'name' => 'battery_is_valid_enabled',
                'type' => 'CheckBox',
                'caption' => 'battery is valid',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('battery_is_valid_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "battery_is_valid_enabled", $battery_is_valid_enabled);'],
            [
                'name' => 'rssi_enabled',
                'type' => 'CheckBox',
                'caption' => 'rssi',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('rssi_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "rssi_enabled", $rssi_enabled);'],
            [
                'name' => 'rssi_is_valid_enabled',
                'type' => 'CheckBox',
                'caption' => 'rssi is valid',
                'visible' => true,
                'value' => $this->ReadAttributeBoolean('rssi_is_valid_enabled'),
                'onChange' => 'Ondilo_SetWebFrontVariable($id, "rssi_is_valid_enabled", $rssi_is_valid_enabled);'],
            [
                'type' => 'Button',
                'visible' => true,
                'caption' => 'Read Pool Information',
                'onClick' => 'ONDILO_ReadPoolInformation($id);'
            ]
        ];
        return $form;
    }

    /**
     * return from status
     * @return array
     */
    protected function FormStatus()
    {
        $form = [
            [
                'code' => IS_CREATING,
                'icon' => 'inactive',
                'caption' => 'Creating instance.'
            ],
            [
                'code' => IS_ACTIVE,
                'icon' => 'active',
                'caption' => 'Ondilo device created.'
            ],
            [
                'code' => IS_INACTIVE,
                'icon' => 'inactive',
                'caption' => 'interface closed.'
            ],
            [
                'code' => 205,
                'icon' => 'error',
                'caption' => 'This device can only created by the Ondilo configurator, please use the Ondilo configurator for creating Ondilo devices.'
            ]
        ];

        return $form;
    }
}
