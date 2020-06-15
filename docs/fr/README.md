# IPSymconOndilo
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/37412-IP-Symcon-5-0-%28Testing%29)

Module pour IP-Symcon à partir de la version 5. Permet la communication avec un périphérique [Ondilo ICO](https://ondilo.com).

## Documentation

**Table of Contents**

1. [Caractéristiques](#1-caractéristiques)
2. [exigences](#2-exigences)
3. [Installation](#3-installation)
4. [Référence de fonction](#4-référence de fonction)
5. [Configuration](#5-configuration)
6. [Attachement](#6-attachement)

## 1. Caractéristiques

Lecture des données de mesure d'un appareil Ondilo ICO via l'API Ondilo ICO.
	  
## 2. Exigences

 - IPS 5.2
 - Compte utilisateur Ondilo ICO
 - [Ondilo ICO](https://ondilo.com)
 - IP-Symcon Connect

## 3. Installation

### a. Charger le module

Ouvrez la console Web de la console IP avec _http://{IP-Symcon IP}:3777/console/_.

Cliquez ensuite sur l'icône du magasin de modules (IP-Symcon> 5.2) dans le coin supérieur droit.

![Store](img/store_icon.png?raw=true "open store")

Dans le champ de recherche, tapez

```
Ondilo ICO
```  


![Store](img/module_store_search_en.png?raw=true "module search")

Then select the module and click _Install_

![Store](img/install_en.png?raw=true "install")

### b. Ondilo Cloud
Un compte avec Ondilo est requis, qui est utilisé pour l'OIC Ondilo.

Pour accéder à Ondilo ICO via l'API Ondilo, IP-Symcon doit d'abord être authentifié en tant que système.
Cela nécessite un IP-Symcon Connect actif et le nom d'utilisateur et le mot de passe Ondilo normaux.
Tout d'abord, lors de l'installation du module, il vous est demandé si vous souhaitez créer une instance de configuration, vous y répondez avec _oui_, mais vous pouvez également créer vous-même l'instance de configurateur

### c. Authentification sur Ondilo
Ensuite, une fenêtre Configurer l'interface apparaît, ici vous appuyez sur le bouton _Register_ et préparez votre nom d'utilisateur et votre mot de passe Ondilo.

![Interface](img/register.png?raw=true "interface")

Ondilo's login page opens. Here you enter the Ondilo user name and the Ondilo password in the mask and continue by clicking on _Authorize_.

![Anmeldung](img/oauth_1_fr.png?raw=true "Anmeldung")

Maintenant que vous avez confirmé avec Ondilo que IP-Symcon est autorisé à lire les données du compte utilisateur Ondilo ICO, vous accédez à la page de confirmation.

![Success](img/oauth_2.png?raw=true "Success")

Une confirmation par IP-Symcon apparaît que l'authentification a réussi,
alors la fenêtre du navigateur peut être fermée et vous revenez à IP-Symcon.
De retour dans la fenêtre Configurer l'interface, accédez à _Suivant_

Nous ouvrons maintenant l'instance du configurateur dans l'arborescence d'objets sous _configurator instances_.

### d. Installation du module configurateur

Passons maintenant à l'instance _**Ondilo**_ (type configurateur Ondilo) dans l'arborescence d'objets sous _Configurator instances_.

Tous les appareils enregistrés avec Ondilo sous le compte et pris en charge par l'API Ondilo sont répertoriés ici.

Un seul appareil peut être créé en marquant l'appareil et en appuyant sur le bouton _Créer_. Le configurateur crée ensuite une instance d'appareil.

### e. Configuration de l'instance de périphérique
La configuration manuelle d'un module d'appareil n'est pas nécessaire, cela se fait à l'aide du configurateur. Les variables individuelles peuvent toujours être activées dans le module d'appareil pour être affichées sur le Web avant si nécessaire.

## 4. Référence de fonction

### a. Vue de face Web

![Webfront](img/webfront_ico.png?raw=true "Webfront")  

## 5. Configuration:




## 6. Attachement

###  GUID et échange de données:

#### Ondilo Cloud:

GUID: `{703B7E3E-5531-71FA-5905-AE11110DDD7E}` 


#### Ondilo Device:

GUID: `{78C7A7D8-6E03-E200-7E9C-11B47D1A50DE}` 
