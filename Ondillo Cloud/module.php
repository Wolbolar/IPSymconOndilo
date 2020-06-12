<?php
declare(strict_types=1);

class OndiloCloud extends IPSModule
{
    private $oauthIdentifer = 'ondilo';

    // Ondilo API
    private const ONDILO_URL = 'https://interop.ondilo.com';
    private const USER_INFORMATION = '/api/customer/v1/user/info';
    private const USER_UNITS = '/api/customer/v1/user/units';
    private const LIST_POOLS = '/api/customer/v1/pools';
    private const DEVICE = '/device';
    private const CONFIGURATION = '/configuration';
    private const SHARES = '/shares';
    private const LAST_MEASURES = '/lastmeasures';
    private const SET_OF_MEASURES = '/measures';
    private const RECOMMENDATIONS = '/recommendations';
    private const MICRO_SIEMENS_PER_CENTI_METER = 'MICRO_SIEMENS_PER_CENTI_METER';
    private const FRENCH_DEGREE = 'FRENCH_DEGREE';
    private const MILLI_VOLT = 'MILLI_VOLT';
    private const HECTO_PASCAL = 'HECTO_PASCAL';
    private const GRAM_PER_LITER = 'GRAM_PER_LITER';
    private const METER_PER_SECOND = 'METER_PER_SECOND';
    private const CELSIUS = 'CELSIUS';
    private const CUBIC_METER = 'CUBIC_METER';


    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RegisterPropertyInteger("UpdateInterval", 15);
        $this->RegisterTimer("Update", 0, "ONDILO_Update(" . $this->InstanceID . ");");
        $this->RegisterAttributeString('Token', '');

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
        $this->RegisterAttributeString('list_pools', '[]');




        $this->RegisterPropertyInteger("ImportCategoryID", 0);

        //we will wait until the kernel is ready
        $this->RegisterMessage(0, IPS_KERNELMESSAGE);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        if (IPS_GetKernelRunlevel() !== KR_READY) {
            return;
        }

        $this->RegisterOAuth($this->oauthIdentifer);
        $ondilo_interval = $this->ReadPropertyInteger('UpdateInterval');
        $this->SetOndiloInterval($ondilo_interval);

        if ($this->ReadAttributeString('Token') == '') {
            $this->SetStatus(IS_INACTIVE);
        } else {
            $this->SetStatus(IS_ACTIVE);
        }
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
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

    public function Update()
    {
        $this->UpdateStatus();
    }

    public function UpdateStatus()
    {
        $this->GetUserInformationData();
        $this->GetUserUnitsData();
        $list_of_pools_json = $this->GetListPools();
        $list_of_pools = json_decode($list_of_pools_json);
        if($list_of_pools != false)
        {
            foreach($list_of_pools as $key => $pool)
            {
                $pool_id = $pool->id;
                $pool_name = $pool->name;
                $this->SendDebug('Ondlio Pool', $pool_name. ', ID: ' . $pool_id, 0);
                $last_measure = json_decode($this->GetLastMeasureData($pool_id));
                $this->SendDebug('Send Last Measure Pool' .$pool_name. ', ID: ' . $pool_id, json_encode($last_measure), 0);
                $this->SendDataToChildren(json_encode(Array("DataID" => "{9FBA7489-CF87-05C7-012B-DD1241B3FCB1}", "Buffer" => $last_measure)));
            }
        }
    }

    private function SetOndiloInterval($ondilo_interval): void
    {
        if($ondilo_interval < 15 && $ondilo_interval != 0)
        {
            $ondilo_interval = 15;
        }
        $interval     = $ondilo_interval * 1000  * 60; // minutes
        $this->SetTimerInterval('Update', $interval);
    }

    public function CheckToken()
    {
        $token = $this->ReadAttributeString('Token');
        return $token;
    }

    public function GetToken()
    {
        $token = $this->FetchAccessToken();
        return $token;
    }

    private function RegisterOAuth($WebOAuth)
    {
        $ids = IPS_GetInstanceListByModuleID('{F99BF07D-CECA-438B-A497-E4B55F139D37}');
        if (count($ids) > 0) {
            $clientIDs = json_decode(IPS_GetProperty($ids[0], 'ClientIDs'), true);
            $found = false;
            foreach ($clientIDs as $index => $clientID) {
                if ($clientID['ClientID'] == $WebOAuth) {
                    if ($clientID['TargetID'] == $this->InstanceID) {
                        return;
                    }
                    $clientIDs[$index]['TargetID'] = $this->InstanceID;
                    $found = true;
                }
            }
            if (!$found) {
                $clientIDs[] = ['ClientID' => $WebOAuth, 'TargetID' => $this->InstanceID];
            }
            IPS_SetProperty($ids[0], 'ClientIDs', json_encode($clientIDs));
            IPS_ApplyChanges($ids[0]);
        }
    }

    /**
     * This function will be called by the register button on the property page!
     */
    public function Register()
    {

        //Return everything which will open the browser
        return 'https://oauth.ipmagic.de/authorize/' . $this->oauthIdentifer . '?username=' . urlencode(IPS_GetLicensee());
    }

    /** Exchange our Authentication Code for a permanent Refresh Token and a temporary Access Token
     * @param $code
     *
     * @return mixed
     */
    private function FetchRefreshToken($code)
    {
        $this->SendDebug('FetchRefreshToken', 'Use Authentication Code to get our precious Refresh Token!', 0);
        $this->SendDebug('Recieved Authentication Code', $code, 0);

         $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query(['code' => $code])]];
        $context = stream_context_create($options);
        $result = file_get_contents('https://oauth.ipmagic.de/access_token/' . $this->oauthIdentifer, false, $context);

        $data = json_decode($result);
        $this->SendDebug('Symcon Connect Data', $result, 0);
        if (!isset($data->token_type) || $data->token_type != 'Bearer') {
            die('Bearer Token expected');
        }

        //Save temporary access token
        $this->FetchAccessToken($data->access_token, time() + $data->expires_in);

        //Return RefreshToken
        return $data->refresh_token;
    }

    /**
     * This function will be called by the OAuth control. Visibility should be protected!
     */
    protected function ProcessOAuthData()
    {

        // <REDIRECT_URI>?code=<AUTHORIZATION_CODE>&state=<STATE>
        if ($_SERVER['REQUEST_METHOD'] == 'GET') {
            if (!isset($_GET['code'])) {
                die('Authorization Code expected');
            }

            $token = $this->FetchRefreshToken($_GET['code']);

            $this->SendDebug('ProcessOAuthData', "OK! Let's save the Refresh Token permanently", 0);

            $this->WriteAttributeString('Token', $token);

            //This will enforce a reload of the property page. change this in the future, when we have more dynamic forms
            IPS_ApplyChanges($this->InstanceID);
        } else {

            //Just print raw post data!
            $payload = file_get_contents('php://input');
            $this->SendDebug('OAuth Response', $payload, 0);
        }
    }

    private function FetchAccessToken($Token = '', $Expires = 0)
    {

        //Exchange our Refresh Token for a temporary Access Token
        if ($Token == '' && $Expires == 0) {

            //Check if we already have a valid Token in cache
            $data = $this->GetBuffer('AccessToken');
            if ($data != '') {
                $data = json_decode($data);
                if (time() < $data->Expires) {
                    $this->SendDebug('FetchAccessToken', 'OK! Access Token is valid until ' . date('d.m.y H:i:s', $data->Expires), 0);
                    return $data->Token;
                }
            }

            $this->SendDebug('FetchAccessToken', 'Use Refresh Token to get new Access Token!', 0);
            $options = [
                'http' => [
                    'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                    'method' => 'POST',
                    'content' => http_build_query(['refresh_token' => $this->ReadAttributeString('Token')])]];
            $context = stream_context_create($options);
            $result = file_get_contents('https://oauth.ipmagic.de/access_token/' . $this->oauthIdentifer, false, $context);

            $data = json_decode($result);
            $this->SendDebug('Symcon Connect Data', $result, 0);
            if (!isset($data->token_type) || $data->token_type != 'Bearer') {
                die('Bearer Token expected');
            }

            //Update parameters to properly cache it in the next step
            $Token = $data->access_token;
            $Expires = time() + $data->expires_in;

            //Update Refresh Token if we received one! (This is optional)
            if (isset($data->refresh_token)) {
                $this->SendDebug('FetchAccessToken', "NEW! Let's save the updated Refresh Token permanently", 0);

                $this->WriteAttributeString('Token', $data->refresh_token);
            }
        }

        $this->SendDebug('FetchAccessToken', 'CACHE! New Access Token is valid until ' . date('d.m.y H:i:s', $Expires), 0);

        //Save current Token
        $this->SetBuffer('AccessToken', json_encode(['Token' => $Token, 'Expires' => $Expires]));

        //Return current Token
        return $Token;
    }

    private function FetchData($url)
    {

        $this->SendDebug("AT", $this->FetchAccessToken(), 0);

        $opts = array(
            "http" => array(
                "method" => "GET",
                "header" => "Authorization: Bearer " . $this->FetchAccessToken() . "\r\nAccept: application/json\r\nAccept-Charset: utf-8\r\nAccept-Encoding: gzip, deflate\r\n",
                "ignore_errors" => true
            )
        );
        $context = stream_context_create($opts);

        $result = file_get_contents($url, false, $context);
        $http_error = $http_response_header[0];
        $result = $this->GetErrorMessage($http_error, $result);
        return $result;
    }

    private function GetErrorMessage($http_error, $result)
    {
        $response =  $result;
        if ((strpos($http_error, '200') > 0)) {
            $this->SendDebug('HTTP Response Header',  'Success. Response Body: ' . $result, 0);
        }
        elseif((strpos($http_error, '201') > 0)) {
            $this->SendDebug('HTTP Response Header',  'Success. CreatedResponse Body: ' . $result, 0);
        }
        elseif((strpos($http_error, '400') > 0)) {
            $this->SendDebug('HTTP Response Header', 'There is a problem with some data sent in the request. An error response body shall clarify the issue. Response Body: ' . $result, 0);
            $response =  '[]';
        }
        elseif((strpos($http_error, '401') > 0)) {
            $this->SendDebug('HTTP Response Header', 'The access token sent in the header is invalid. You should call the refresh token endpoint of the Ondilo authentication service. Response Body: ' . $result, 0);
            $response =  '[]';
        }
        elseif((strpos($http_error, '404') > 0)) {
            $this->SendDebug('HTTP Response Header', 'You are trying to reach a page that doesn\'t exist. Response Body: ' . $result, 0);
            $response =  '[]';
        }
        elseif((strpos($http_error, '500') > 0)) {
            $this->SendDebug('HTTP Response Header', 'An error in the Ondilo Customer API application logic has been detected. An error response body shall clarify the issue. Response Body: ' . $result, 0);
            $response =  '[]';
        }
        elseif((strpos($http_error, '503') > 0)) {
            $this->SendDebug('HTTP Response Header', 'The Ondilo Customer API is not currently available. You should try again later. Response Body: ' . $result, 0);
            $response =  '[]';
        }
        else{
            $this->SendDebug('HTTP Response Header', $http_error . ' Response Body: ' . $result, 0);
            $response =  '[]';
        }
        if($result == '{"message":"Limit Exceeded"}')
        {
            $this->SendDebug('Ondilo API', 'Limit Exceeded', 0);
        }
        return $response;
    }

    // Ondilo API

    // User data

    /** User information
     * @return string
     */
    private function GetUserInformationData()
    {
        $user_info_json = $this->FetchData(self::ONDILO_URL . self::USER_INFORMATION);
        $user_info = json_decode($user_info_json);
        if($user_info != false)
        {
            $user_lastname = $user_info->lastname;
            $this->WriteAttributeString('user_lastname', $user_lastname);
            $user_firstname = $user_info->firstname;
            $this->WriteAttributeString('user_firstname', $user_firstname);
            $user_email = $user_info->email;
            $this->WriteAttributeString('user_email', $user_email);
        }
        return $user_info_json;
    }

    /** User Units
     * @return string
     */
    private function GetUserUnitsData()
    {
        $user_units_json = $this->FetchData(self::ONDILO_URL . self::USER_UNITS);
        $user_units = json_decode($user_units_json);
        if($user_units != false)
        {
            $conductivity = $user_units->conductivity;
            $this->SendDebug('Ondlio conductivity', $conductivity, 0);
            $this->WriteAttributeString('unit_conductivity', $conductivity);
            $hardness = $user_units->hardness;
            $this->SendDebug('Ondlio hardness', $hardness, 0);
            $this->WriteAttributeString('unit_hardness', $hardness);
            $orp = $user_units->orp;
            $this->SendDebug('Ondlio orp', $orp, 0);
            $this->WriteAttributeString('unit_orp', $orp);
            $pressure = $user_units->pressure;
            $this->SendDebug('Ondlio pressure', $pressure, 0);
            $this->WriteAttributeString('unit_pressure', $pressure);
            $salt = $user_units->salt;
            $this->SendDebug('Ondlio salt', $salt, 0);
            $this->WriteAttributeString('unit_salt', $salt);
            $speed = $user_units->speed;
            $this->SendDebug('Ondlio speed', $speed, 0);
            $this->WriteAttributeString('unit_speed', $speed);
            $temperature = $user_units->temperature;
            $this->SendDebug('Ondlio temperature', $temperature, 0);
            $this->WriteAttributeString('unit_temperature', $temperature);
            $volume = $user_units->volume;
            $this->SendDebug('Ondlio volume', $volume, 0);
            $this->WriteAttributeString('unit_volume', $volume);
            $this->WriteAttributeString('user_units', json_encode($user_units));
        }
        return $user_units_json;
    }

    // Pool/spa data

    /** List of pools/spas
     * @return string
     */
    public function GetListPools()
    {
        $list_of_pools_json = $this->FetchData(self::ONDILO_URL . self::LIST_POOLS);
        $list_of_pools = json_decode($list_of_pools_json);
        if($list_of_pools != false)
        {
            foreach($list_of_pools as $key => $pool)
            {
                $id = $pool->id;
                $this->SendDebug('Ondlio Pool ID', $id, 0);
            }
            $this->WriteAttributeString('list_pools', json_encode($list_of_pools));
        }
        return $list_of_pools_json;
    }

    /** Pool/spa device
     * @return string
     */
    private function GetPoolDeviceData(string $pool_id)
    {
        return $this->FetchData(self::ONDILO_URL . self::LIST_POOLS . '/' . $pool_id . self::DEVICE);
    }

    /** Pool/spa configuration
     * @return string
     */
    private function GetPoolConfigurationData(string $pool_id)
    {
        return $this->FetchData(self::ONDILO_URL . self::LIST_POOLS . '/' . $pool_id . self::CONFIGURATION);
    }

    /** Pool/spa shares
     * @return string
     */
    private function GetPoolSharesData(string $pool_id)
    {
        return $this->FetchData(self::ONDILO_URL . self::LIST_POOLS . '/' . $pool_id . self::SHARES);
    }

    /** Last measure
     * @return string
     */
    private function GetLastMeasureData($pool_id)
    {
        $last_measure = $this->FetchData(self::ONDILO_URL . self::LIST_POOLS . '/' . $pool_id . self::LAST_MEASURES);
        $this->SendDebug('Last Measure',  $last_measure, 0);
        return $last_measure;
    }

    /** Set of measures
     * @return string
     */
    private function GetSetOfMeasuresData(string $pool_id)
    {
        $last_measure_set = $this->FetchData(self::ONDILO_URL . self::LIST_POOLS . '/' . $pool_id . self::SET_OF_MEASURES);
        /*
        if($last_measure_set != false)
        {
            $conductivity = $last_measure_set->conductivity;
            $this->SendDebug('Ondlio conductivity', $conductivity, 0);
            $hardness = $last_measure_set->hardness;
            $this->SendDebug('Ondlio hardness', $hardness, 0);
            $orp = $$last_measure_set->orp;
            $this->SendDebug('Ondlio orp', $orp, 0);
            $pressure = $last_measure_set->pressure;
            $this->SendDebug('Ondlio pressure', $pressure, 0);
            $salt = $last_measure_set->salt;
            $this->SendDebug('Ondlio conductivity', $salt, 0);
            $speed = $last_measure_set->speed;
            $this->SendDebug('Ondlio speed', $speed, 0);
            $temperature = $last_measure_set->temperature;
            $this->SendDebug('Ondlio temperature', $temperature, 0);
            $volume = $last_measure_set->volume;
            $this->SendDebug('Ondlio volume', $volume, 0);
            $this->WriteAttributeString('user_units', $last_measure_set);
        }
        */
        return $last_measure_set;
    }

    /** List active recommendations
     * @return string
     */
    private function GetListActiveRecommendationsData(string $pool_id)
    {
        $recomendations = $this->FetchData(self::ONDILO_URL . self::LIST_POOLS . '/' . $pool_id . self::RECOMMENDATIONS);
        /*
        if($user_units != false)
        {
            $conductivity = $user_units->conductivity;
            $this->SendDebug('Ondlio conductivity', $conductivity, 0);
            $hardness = $user_units->hardness;
            $this->SendDebug('Ondlio hardness', $hardness, 0);
            $orp = $user_units->orp;
            $this->SendDebug('Ondlio orp', $orp, 0);
            $pressure = $user_units->pressure;
            $this->SendDebug('Ondlio pressure', $pressure, 0);
            $salt = $user_units->salt;
            $this->SendDebug('Ondlio conductivity', $salt, 0);
            $speed = $user_units->speed;
            $this->SendDebug('Ondlio speed', $speed, 0);
            $temperature = $user_units->temperature;
            $this->SendDebug('Ondlio temperature', $temperature, 0);
            $volume = $user_units->volume;
            $this->SendDebug('Ondlio volume', $volume, 0);
            $this->WriteAttributeString('user_units', $user_units);
        }
        */
        return $recomendations;
    }

    /** Validate recommendation
     * @return string
     */
    private function ValidateRecommendation(string $pool_id)
    {
        $recomendations = $this->FetchData(self::ONDILO_URL . self::LIST_POOLS . '/' . $pool_id . self::RECOMMENDATIONS); // todo
        /*
        if($user_units != false)
        {
            $conductivity = $user_units->conductivity;
            $this->SendDebug('Ondlio conductivity', $conductivity, 0);
            $hardness = $user_units->hardness;
            $this->SendDebug('Ondlio hardness', $hardness, 0);
            $orp = $user_units->orp;
            $this->SendDebug('Ondlio orp', $orp, 0);
            $pressure = $user_units->pressure;
            $this->SendDebug('Ondlio pressure', $pressure, 0);
            $salt = $user_units->salt;
            $this->SendDebug('Ondlio conductivity', $salt, 0);
            $speed = $user_units->speed;
            $this->SendDebug('Ondlio speed', $speed, 0);
            $temperature = $user_units->temperature;
            $this->SendDebug('Ondlio temperature', $temperature, 0);
            $volume = $user_units->volume;
            $this->SendDebug('Ondlio volume', $volume, 0);
            $this->WriteAttributeString('user_units', $user_units);
        }
        */
        return $recomendations;
    }

    private function PutData($url, $content)
    {
        $this->SendDebug("AT", $this->FetchAccessToken(), 0);

        $opts = array(
            "http" => array(
                "method" => "PUT",
                "header" => "Authorization: Bearer " . $this->FetchAccessToken() . "\r\nAuthorization-Provider: husqvarna\r\nX-Api-Key: " . self::APIKEY . "\r\n" . 'Content-Type: application/json' . "\r\n"
                    . 'Content-Length: ' . strlen($content) . "\r\n",
                'content' => $content,
                "ignore_errors" => true
            )
        );
        $context = stream_context_create($opts);

        $result = file_get_contents($url, false, $context);
        $http_error = $http_response_header[0];
        $result = $this->GetErrorMessage($http_error, $result);
        return $result;
    }

    private function PostData($url, $content)
    {

        $this->SendDebug("AT", $this->FetchAccessToken(), 0);
        $opts = array(
            "http" => array(
                "method" => "POST",
                "header" => "Authorization: Bearer " . $this->FetchAccessToken() . "\r\nX-Api-Key: " . self::APIKEY . "\r\n" . 'Content-Type: application/vnd.api+json' . "\r\n"
                    . 'Content-Length: ' . strlen($content) . "\r\n",
                'content' => $content,
                "ignore_errors" => true
            )
        );
        $context = stream_context_create($opts);

        $result = file_get_contents($url, false, $context);
        $http_error = $http_response_header[0];
        $result = $this->GetErrorMessage($http_error, $result);
        return $result;
    }

    public function ForwardData($data)
    {
        $data = json_decode($data);
        $id = $data->id;
        if (strlen($data->Payload) > 0) {
            $type = $data->Type;
            if($type == 'PUT')
            {
                $this->SendDebug('ForwardData', $data->Endpoint . ', Payload: ' . $data->Payload, 0);
                $response = $this->PutData(self::ONDILO_URL . $data->Endpoint, $data->Payload);
            }
            elseif($type == 'POST')
            {
                $this->SendDebug('ForwardData', $data->Endpoint . ', Payload: ' . $data->Payload, 0);
                $response = $this->PostData(self::ONDILO_URL . $data->Endpoint, $data->Payload);
            }
        } else {
            $this->SendDebug('ForwardData', $data->Endpoint, 0);
            if($data->Endpoint == 'GetUserInformation')
            {
                $response = $this->GetUserInformationData();
            }
            elseif($data->Endpoint == 'GetUserUnits')
            {
                $response = $this->GetUserUnitsData();
            }
            elseif($data->Endpoint == 'GetPoolDevice')
            {
                $response = $this->GetPoolDeviceData($id);
            }
            elseif($data->Endpoint == 'GetPoolConfiguration')
            {
                $response = $this->GetPoolConfigurationData($id);
            }
            elseif($data->Endpoint == 'GetPoolDevice')
            {
                $response = $this->GetPoolDeviceData($id);
            }
            elseif($data->Endpoint == 'GetPoolShares')
            {
                $response = $this->GetPoolSharesData($id);
            }
            elseif($data->Endpoint == 'GetLastMeasure')
            {
                $response = $this->GetLastMeasureData($id);
            }
            elseif($data->Endpoint == 'GetSetOfMeasures')
            {
                $response = $this->GetSetOfMeasuresData($id);
            }
            elseif($data->Endpoint == 'GetListPools')
            {
                $response = $this->GetListPools();
            }
            elseif($data->Endpoint == 'token')
            {
                $response = $this->CheckToken();
            }

        }
        return $response;
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
        $Form = json_encode([
            'elements' => $this->FormHead(),
            'actions' => $this->FormActions(),
            'status' => $this->FormStatus()
        ]);
        $this->SendDebug('FORM', $Form, 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return $Form;
    }

    /**
     * return form configurations on configuration step
     * @return array
     */
    protected function FormHead()
    {
        $visibility_register = false;
        //Check Ondilo connection
        if ($this->ReadAttributeString('Token') == '') {
            $visibility_register = true;
        }

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
                        'label' => $this->ReadAttributeString('user_firstname')
                    ],
                    [
                        'type' => 'Label',
                        'label' => $this->ReadAttributeString('user_lastname')
                    ],]],
            [
                'type' => 'Label',
                'visible' => $visibility_register,
                'caption' => 'Ondilo: Please register with your Ondilo account!'
            ],
            [
                'type' => 'Button',
                'visible' => true,
                'caption' => 'Register',
                'onClick' => 'echo ONDILO_Register($id);'
            ],
            [
                'type' => 'Label',
                'visible' => true,
                'label' => 'Update interval in minutes (minimum 15 minutes):'
            ],
            [
                'name' => 'UpdateInterval',
                'visible' => true,
                'type' => 'IntervalBox',
                'caption' => 'minutes'
            ]
        ];
        return $form;
    }

    /**
     * return form actions by token
     * @return array
     */
    protected function FormActions()
    {
        //Check Connect availability
        $ids = IPS_GetInstanceListByModuleID('{9486D575-BE8C-4ED8-B5B5-20930E26DE6F}');
        if (IPS_GetInstance($ids[0])['InstanceStatus'] != IS_ACTIVE) {
            $visibility_label1 = true;
            $visibility_label2 = false;
        } else {
            $visibility_label1 = false;
            $visibility_label2 = true;
        }
        $visibility_config = true;
       $form = [
            [
                'type' => 'Label',
                'visible' => $visibility_label1,
                'caption' => 'Error: Symcon Connect is not active!'
            ],
            [
                'type' => 'Label',
                'visible' => $visibility_label2,
                'caption' => 'Status: Symcon Connect is OK!'
            ],
           [
               'type' => 'Label',
               'visible' => $visibility_config,
               'caption' => $this->Translate('Ondilo Location: ')
           ],
            [
                'type' => 'Label',
                'visible' => true,
                'caption' => 'Read Ondilo configuration:'
            ],
            [
                'type' => 'Button',
                'visible' => true,
                'caption' => 'Read configuration',
                'onClick' => 'ONDILO_UpdateStatus($id);'
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
                'caption' => 'configuration valid.'
            ],
            [
                'code' => IS_INACTIVE,
                'icon' => 'inactive',
                'caption' => 'interface closed.'
            ],
            [
                'code' => 201,
                'icon' => 'inactive',
                'caption' => 'Please follow the instructions.'
            ],
            [
                'code' => 202,
                'icon' => 'error',
                'caption' => 'no category selected.'
            ]
        ];

        return $form;
    }
}