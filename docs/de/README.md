# IPSymconOndilo
[![Version](https://img.shields.io/badge/Symcon-PHPModul-red.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-5.0%20%3E-green.svg)](https://www.symcon.de/forum/threads/38222-IP-Symcon-5-0-verf%C3%BCgbar)

Modul für IP-Symcon ab Version 5. Ermöglicht die Kommunikation mit einem Ondilo ICO Gerät.

## Dokumentation

**Inhaltsverzeichnis**

1. [Funktionsumfang](#1-funktionsumfang)  
2. [Voraussetzungen](#2-voraussetzungen)  
3. [Installation](#3-installation)  
4. [Funktionsreferenz](#4-funktionsreferenz)  
5. [Anhang](#5-anhang)  

## 1. Funktionsumfang

Auslesen der Messdaten eines Ondilo ICO Gerätes über die Ondilo ICO API.

## 2. Voraussetzungen

 - IPS 5.2
 - Ondilo ICO Benutzerkonto
 - Ondilo ICO
 - IP-Symcon Connect

## 3. Installation

### a. Laden des Moduls

Die Webconsole von IP-Symcon mit _http://{IP-Symcon IP}:3777/console/_ öffnen. 


Anschließend oben rechts auf das Symbol für den Modulstore (IP-Symcon > 5.2) klicken

![Store](img/store_icon.png?raw=true "open store")

Im Suchfeld nun

```
Ondilo ICO
```  

eingeben

![Store](img/module_store_search.png?raw=true "module search")

und schließend das Modul auswählen und auf _Installieren_

![Store](img/install.png?raw=true "install")

drücken.

### b. Ondilo-Cloud
Es wird ein Account bei Ondilo benötigt, den man für den Ondilo ICO nutzt.

Um Zugriff auf den Ondilo ICO über die Ondilo API zu erhalten muss zunächst IP-Symcon als System authentifiziert werden.
Hierzu wird ein aktives IP-Symcon Connect benötigt und den normalen Ondilo Benutzernamen und Passwort.
Zunächst wird beim installieren des Modul gefragt ob eine Konfigurator Instanz angelegt werden soll, dies beantwortet man mit _ja_, man kann aber auch die Konfigurator Instanz von Hand selber anlegen

### c. Authentifizierung bei Ondilo
Anschließend erscheint ein Fenster Schnittstelle konfigurieren, hier drückt man auf den Knopf _Registrieren_ und hält seinen Ondilo ICO Benutzernamen und Passwort bereit.

![Schnittstelle](img/register.png?raw=true "Schnittstelle")

Es öffnet sich die Anmeldeseite von Ondilo. Hier gibt man in die Maske den Ondilo Benutzernamen und das Ondilo Passwort an und fährt mit einem Klick auf _Authorize_ fort.

![Anmeldung](img/oauth_1.png?raw=true "Anmeldung")

Nachdem man jetzt bei Ondilo bestätigt hat das IP-Symcon die Daten des Ondilo ICO Benutzerkontos auslesen darf gelangt man zur Bestätigungsseite.

![Success](img/oauth_2.png?raw=true "Success")

Es erscheint dann eine Bestätigung durch IP-Symcon das die Authentifizierung erfolgreich war,
anschließend kann das Browser Fenster geschlossen werden und man kehrt zu IP-Symcon zurück.
Zurück beim Fenster Schnittstelle konfigurieren geht man nun auf _Weiter_

Nun öffnen wir die Konfigurator Instanz im Objekt Baum zu finden unter _Konfigurator Instanzen_. 

### d. Einrichtung des Konfigurator-Moduls

Jetzt wechseln wir im Objektbaum in die Instanz _**Ondilo**_ (Typ Ondilo Konfigurator) zu finden unter _Konfigurator Instanzen_.

Hier werden alle Geräte, die bei Ondilo unter dem Account registiert sind und von der Ondilo API unterstützt werden aufgeführt.

Ein einzelnes Gerät kann man durch markieren auf das Gerät und ein Druck auf den Button _Erstellen_ erzeugen. Der Konfigurator legt dann eine Geräte Instanz an.

### e. Einrichtung der Geräteinstanz
Eine manuelle Einrichtung eines Gerätemoduls ist nicht erforderlich, das erfolgt über den Konfigurator. In dem Geräte-Modul können noch einzelne Variablen bei Bedarf zur Anzeige im Webfront freigeschaltet werden.


## 4. Funktionsreferenz

### a. Webfront Ansicht

![Webfront](img/webfront_ico.png?raw=true "Webfront")  

## 5. Konfiguration:



## 6. Anhang

###  GUIDs und Datenaustausch:

#### Ondilo Cloud:

GUID: `{703B7E3E-5531-71FA-5905-AE11110DDD7E}` 


#### Ondilo Device:

GUID: `{78C7A7D8-6E03-E200-7E9C-11B47D1A50DE}` 