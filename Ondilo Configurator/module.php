<?php
declare(strict_types=1);

class OndiloConfigurator extends IPSModule
{
    private const OUTDOOR_INGROUND_POOL = 'outdoor_inground_pool';

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->ConnectParent('{703B7E3E-5531-71FA-5905-AE11110DDD7E}');
        $this->RegisterPropertyInteger("ImportCategoryID", 0);
        $this->RegisterAttributeString('location_snapshot', '[]');
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();

        $import_category = $this->ReadPropertyInteger('ImportCategoryID');
        if($import_category == 0)
        {
            $this->SetStatus(202);
        }
        /*
        $token = $this->GetGardenaToken();
        if ($token == '') {
            $this->SendDebug('Gardena Token', $token, 0);
            $this->SendDebug('Gardena Token', 'Instance set inactive', 0);
            $this->SetStatus(IS_INACTIVE);
        } else {
            $this->SetStatus(IS_ACTIVE);
        }
        */
        $this->SetStatus(IS_ACTIVE);
    }

    public function GetOndiloToken()
    {
        $token = $this->RequestDataFromParent('token');
        return $token;
    }

    /** Get Snapshot
     * @return bool|false|string
     */
    public function RequestSnapshot()
    {
        /*
        $location_id = $this->RequestDataFromParent('location_id');
        if ($location_id != '') {
            $snapshot = $this->RequestDataFromParent('snapshotbuffer');
        } else {
            $snapshot = '[]';
        }
        $this->SendDebug('Gardena Request Response', $snapshot, 0);
        $this->WriteAttributeString('location_snapshot', $snapshot);
        return $snapshot;
        */
    }

    public function GetConfiguration()
    {
        /*
        $location_id = $this->RequestDataFromParent('location_id');
        if ($location_id != '') {
            $snapshot = $this->RequestSnapshot();
        } else {
            $locations = $this->RequestLocations();
            if(!$locations === false)
            {
                $snapshot = $this->RequestSnapshot();
            }
        }
        return $snapshot;
        */
    }

    public function RequestDataFromParent(string $endpoint)
    {
        $data = $this->SendDataToParent(json_encode([
            'DataID'   => '{58908109-E534-70C0-8A94-8FAABEEBFC6C}',
            'Type' => 'GET',
            'Endpoint' => $endpoint,
            'id' => 0,
            'Payload'  => ''
        ]));
        $this->SendDebug('Ondilo Request Response', $endpoint . ": " . $data, 0);
        return $data;
    }

    /** Get Device Type
     * @param $device
     * @return array
     */
    private function GetDeviceType($device)
    {
        $data = [];
        $model_type = $device['attributes']['modelType']['value'];
        if ($model_type == 'Ondilo smart Irrigation Control') {
            $data = $this->GetIrrigationControlData($device);
        } elseif ($model_type == 'Ondilo smart Sensor') {
            $data = $this->GetSensorInfo($device);
        }
        elseif ($model_type == 'Ondilo smart Water Control') {
            $data = $this->GetWaterControlData($device);
        }
        return $data;
    }

    /** Get Sensor Info
     * @param $device
     * @return array
     */
    private function GetSensorInfo($device)
    {
        $id = $device['id'];
        $model_type = $device['attributes']['modelType']['value'];
        $name = $device['attributes']['name']['value'];
        $battery_level = $device['attributes']['batteryLevel']['value'];
        $this->SendDebug('Ondilo Device ' . $name, 'battery level: ' . $battery_level . '%', 0);
        $battery_level_timestamp = $device['attributes']['batteryLevel']['timestamp'];
        $this->SendDebug('Ondilo Device ' . $name, 'battery level timestamp: ' . $battery_level_timestamp, 0);
        $battery_state = $device['attributes']['batteryState']['value'];
        $this->SendDebug('Ondilo Device ' . $name, 'battery state: ' . $battery_state, 0);
        $battery_state_timestamp = $device['attributes']['batteryState']['timestamp'];
        $this->SendDebug('Ondilo Device ' . $name, 'battery state timestamp: ' . $battery_state_timestamp, 0);
        $rf_link_level = $device['attributes']['rfLinkLevel']['value'];
        $this->SendDebug('Ondilo Device ' . $name, 'RF link level: ' . $rf_link_level . '%', 0);
        $rf_link_level_timestamp = $device['attributes']['rfLinkLevel']['timestamp'];
        $this->SendDebug('Ondilo Device ' . $name, 'RF link level timestamp: ' . $rf_link_level_timestamp, 0);
        $serial = $device['attributes']['serial']['value'];
        $this->SendDebug('Ondilo Device ' . $name, 'serial: ' . $serial, 0);
        $rf_link_state = $device['attributes']['rfLinkState']['value'];
        $this->SendDebug('Ondilo Device ' . $name, 'RF link state: ' . $rf_link_state, 0);

        return ['id' => $id, 'name' => $name, 'serial' => $serial, 'rf_link_state' => $rf_link_state, 'model_type' => $model_type];
    }

    /** Get Irrigation Control Data
     * @param $device
     * @return array
     */
    private function GetIrrigationControlData($device)
    {
        $id = $device['id'];
        $model_type = $device['attributes']['modelType']['value'];
        $name = $device['attributes']['name']['value'];
        $serial = $device['attributes']['serial']['value'];
        $this->SendDebug('Ondilo Device ' . $name, 'serial: ' . $serial, 0);
        $rf_link_state = $device['attributes']['rfLinkState']['value'];
        $this->SendDebug('Ondilo Device ' . $name, 'RF link state: ' . $rf_link_state, 0);
        return ['id' => $id, 'name' => $name, 'serial' => $serial, 'rf_link_state' => $rf_link_state, 'model_type' => $model_type];
    }

    /** Get Water Control Data
     * @param $device
     * @return array
     */
    private function GetWaterControlData($device)
    {
        $id = $device['id'];
        $model_type = $device['attributes']['modelType']['value'];
        $name = $device['attributes']['name']['value'];
        $serial = $device['attributes']['serial']['value'];
        $this->SendDebug('Ondilo Device ' . $name, 'serial: ' . $serial, 0);
        $rf_link_state = $device['attributes']['rfLinkState']['value'];
        $this->SendDebug('Ondilo Device ' . $name, 'RF link state: ' . $rf_link_state, 0);
        return ['id' => $id, 'name' => $name, 'serial' => $serial, 'rf_link_state' => $rf_link_state, 'model_type' => $model_type];
    }

    /**
     * Liefert alle Geräte.
     *
     * @return array configlist all devices
     */
    private function Get_ListConfiguration()
    {
        $config_list = [];
        // $list_pools = $this->RequestDataFromParent('GetListPools');
        $list_pools = '[
    {
        "id": 234,
        "name": "John\'s Pool",
        "type": "outdoor_inground_pool",
        "volume": 15,
        "disinfection": {
            "primary": "chlorine",
            "secondary": {
                "uv_sanitizer": true,
                "ozonator": false
            }
        },
        "address": {
            "street": "162 Avenue Robert Schuman",
            "zipcode": "13760",
            "city": "Saint-Cannat",
            "country": "France",
            "latitude": 43.612282,
            "longitude": 5.3179397
        },
        "updated_at": "2019-11-27T23:00:21+0000"
    }
]';

        if ($list_pools != '') {
            $OndiloInstanceIDList = IPS_GetInstanceListByModuleID('{78C7A7D8-6E03-E200-7E9C-11B47D1A50DE}'); // Ondilo Devices
            $payload = json_decode($list_pools);
            $counter = count($payload);
            if ($counter > 0) {
                foreach($payload as $key => $pool)
                {
                    $instanceID = 0;
                    $pool_id = $pool->id;
                    $this->SendDebug('Ondlio Pool ID', $pool_id, 0);
                    foreach ($OndiloInstanceIDList as $OndiloInstanceID) {
                        if (IPS_GetProperty($OndiloInstanceID, 'id') == $pool_id) { // todo  InstanceInterface is not available
                            $instanceID = $OndiloInstanceID;
                        }
                    }
                    $name = $pool->name;
                    $type_raw = $pool->type;
                    if($type_raw == self::OUTDOOR_INGROUND_POOL)
                    {
                        $type = 'outdoor inground pool';
                    }
                    else
                    {
                        $this->SendDebug('Ondilo pool type', $type_raw, 0);
                        $type = $type_raw;
                    }
                    $address = $pool->address;
                    $street = $address->street;
                    $zipcode = $address->zipcode;
                    $city = $address->city;
                    $config_list[] = ["instanceID" => $instanceID,
                        "pool_id" => $pool_id,
                        "name" => $name,
                        "type" => $this->Translate($type),
                        "street" => $street,
                        "zipcode" => $zipcode,
                        "city" => $city,
                        "create" => [
                            [
                                "moduleID" => "{78C7A7D8-6E03-E200-7E9C-11B47D1A50DE}",
                                "configuration" => [
                                    "id" => $pool_id,
                                    "name" => $name,
                                ],
                                "location" => $this->SetLocation()
                            ]
                        ]
                    ];

                }
            }
        }
        return $config_list;
    }

    private function SetLocation()
    {
        $category = $this->ReadPropertyInteger("ImportCategoryID");
        $tree_position[] = IPS_GetName($category);
        $parent = IPS_GetObject($category)['ParentID'];
        $tree_position[] = IPS_GetName($parent);
        do {
            $parent = IPS_GetObject($parent)['ParentID'];
            $tree_position[] = IPS_GetName($parent);
        } while ($parent > 0);
        // delete last key
        end($tree_position);
        $lastkey = key($tree_position);
        unset($tree_position[$lastkey]);
        // reverse array
        $tree_position = array_reverse($tree_position);
        return $tree_position;
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

        // $list_pools = $this->RequestDataFromParent('GetListPools');
        $list_pools = '[
    {
        "id": 234,
        "name": "John\'s Pool",
        "type": "outdoor_inground_pool",
        "volume": 15,
        "disinfection": {
            "primary": "chlorine",
            "secondary": {
                "uv_sanitizer": true,
                "ozonator": false
            }
        },
        "address": {
            "street": "162 Avenue Robert Schuman",
            "zipcode": "13760",
            "city": "Saint-Cannat",
            "country": "France",
            "latitude": 43.612282,
            "longitude": 5.3179397
        },
        "updated_at": "2019-11-27T23:00:21+0000"
    }
]';
        if ($list_pools == '') {
            $show_config = false;
        } else {
            $show_config = true;
        }
        $visibility_register = false;
        //Check Ondilo connection
        $token = $this->GetOndiloToken();
        if ($token == '') {
            $this->SendDebug('Token', $token, 0);
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
                        'label' => $this->Translate('Ondilo')
                    ]]],
            [
                'type' => 'Label',
                'visible' => $visibility_register,
                'caption' => 'Ondilo: Please switch to the I/O instance and register with your Ondilo account!'
            ],
            [
                'type' => 'Label',
                'caption' => 'category for Ondilo devices'
            ],
            [
                'name' => 'ImportCategoryID',
                'type' => 'SelectCategory',
                'caption' => 'category Ondilo devices'
            ],
            [
                'name' => 'OndiloConfiguration',
                'type' => 'Configurator',
                'visible' => $show_config,
                'rowCount' => 20,
                'add' => false,
                'delete' => true,
                'sort' => [
                    'column' => 'name',
                    'direction' => 'ascending'
                ],
                'columns' => [
                    [
                        'caption' => 'ID',
                        'name' => 'id',
                        'width' => '200px',
                        'visible' => false
                    ],
                    [
                        'caption' => 'pool_id',
                        'name' => 'pool_id',
                        'width' => '200px',
                        'visible' => false
                    ],
                    [
                        'name' => 'name',
                        'caption' => 'name',
                        'width' => 'auto'
                    ],
                    [
                        'name' => 'type',
                        'caption' => 'type',
                        'width' => '250px'
                    ],
                    [
                        'name' => 'street',
                        'caption' => 'street',
                        'width' => '250px'
                    ],
                    [
                        'name' => 'zipcode',
                        'caption' => 'zipcode',
                        'width' => '150px'
                    ],
                    [
                        'name' => 'city',
                        'caption' => 'city',
                        'width' => '300px'
                    ]
                ],
                'values' => $this->Get_ListConfiguration()
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
                'caption' => 'Read Ondilo configuration:'
            ],
            [
                'type' => 'Button',
                'visible' => $visibility_config,
                'caption' => 'Read configuration',
                'onClick' => 'Ondilo_GetConfiguration($id);'
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