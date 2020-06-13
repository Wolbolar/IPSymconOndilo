# IPSymconOndilo
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/37412-IP-Symcon-5-0-%28Testing%29)

Module for IP-Symcon from version 5. Allows communication with Ondilo ICO devices.

## Documentation

**Table of Contents**

1. [Features](#1-features)
2. [Requirements](#2-requirements)
3. [Installation](#3-installation)
4. [Function reference](#4-functionreference)
5. [Configuration](#5-configuration)
6. [Annex](#6-annex)

## 1. Features

Read data from Ondilo ICO devices via Ondilo ICO Cloud API. 
	  
## 2. Requirements

 - IPS 5.2
 - Ondilo ICO account
 - Ondilo ICO
 - IP-Symcon Connect

## 3. Installation

### a. Loading the module

Open the IP Console's web console with _http://{IP-Symcon IP}:3777/console/_.

Then click on the module store (IP-Symcon > 5.2) icon in the upper right corner.

![Store](img/store_icon.png?raw=true "open store")

In the search field type

```
Ondilo ICO
```  


![Store](img/module_store_search_en.png?raw=true "module search")

Then select the module and click _Install_

![Store](img/install_en.png?raw=true "install")

### b. Ondilo Cloud
An account with Ondilo is required, which is used for the Ondilo ICO.

To get access to the Ondilo ICO via the Ondilo API, IP-Symcon must first be authenticated as a system.
This requires an active IP-Symcon Connect and the normal Ondilo user name and password.
First, when installing the module, you are asked whether you want to create a configutator instance, you answer this with _yes_, but you can also create the configurator instance yourself

### c. Authentication to Ondilo
Then a Configure Interface window appears, here you press the _Register_ button and have your Ondilo user name and password ready.

![Interface](img/register.png?raw=true "interface")

Ondilo's login page opens. Here you enter the Ondilo user name and the Ondilo password in the mask and continue by clicking on _Authorize_.

![Anmeldung](img/oauth_1.png?raw=true "Anmeldung")

Now that you have confirmed with Ondilo that the IP-Symcon is allowed to read out the data of the Ondilo ICO user account you get to the confirmation page.

![Success](img/oauth_2.png?raw=true "Success")

A confirmation by IP-Symcon appears that the authentication was successful,
then the browser window can be closed and you return to IP-Symcon.
Back at the Configure Interface window, go to _Next_

Now we open the configurator instance in the object tree under _configurator instances_.


### d. Setup of the configurator module

Now we switch to the instance _**Ondilo**_ (type Ondilo Configurator) in the object tree under _Configurator Instances_.

All devices that are registered with Ondilo under the account and supported by the Ondilo API are listed here.

A single device can be created by marking the device and pressing the _Create_ button. The configurator then creates a device instance.

### e. Device instance setup
Manual configuration of a device module is not necessary, this is done using the configurator. Individual variables can still be activated in the device module for display on the web front if required.


## 4. Function reference

### a. Webfront View

![Webfront](img/webfront_ico.png?raw=true "Webfront")  

## 5. Configuration:




## 6. Annnex

###  GUIDs und Data Flow:

#### Ondilo Cloud:

GUID: `{703B7E3E-5531-71FA-5905-AE11110DDD7E}` 


#### Ondilo Device:

GUID: `{78C7A7D8-6E03-E200-7E9C-11B47D1A50DE}` 
