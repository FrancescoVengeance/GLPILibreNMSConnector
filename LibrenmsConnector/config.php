<?php

/*
 * @version $Id: HEADER 15930 2011-10-25 10:47:55Z jmd $
  -------------------------------------------------------------------------
  GLPI - Gestionnaire Libre de Parc Informatique
  Copyright (C) 2003-2011 by the INDEPNET Development Team.

  http://indepnet.net/   http://glpi-project.org
  -------------------------------------------------------------------------

  LICENSE

  This file is part of GLPI.

  GLPI is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation; either version 2 of the License, or
  (at your option) any later version.

  GLPI is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with GLPI. If not, see <http://www.gnu.org/licenses/>.
  --------------------------------------------------------------------------
 */

// ----------------------------------------------------------------------
// Original Author of file:
// Purpose of file:
// ----------------------------------------------------------------------
// Non menu entry case
//header("Location:../../central.php");
// Entry menu case
const GLPI_ROOT = '../..';
include (GLPI_ROOT . "/inc/includes.php");
include ("inc/apiconfig.class.php");
Session::checkRight("config", UPDATE);

// To be available when plugin in not activated
Plugin::load('example');

if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
    Html::header("Netdiscovery | Config", $_SERVER['PHP_SELF'], "config", "plugins");
    $serverip = ApiConfig::getInstance()->getServerIp();
    $apiKey = ApiConfig::getInstance()->getApiKey();
    $token = Session::getNewCSRFToken(true);
    if (isset($_POST["testconnection"])) {
        $res = ApiConfig::getInstance()->executeQuery('system');
        echo '<h3>' . (($res['count'] == 1) ? "Conection OK" : "Connection FAILED") . '</h3><br />';
    }

    echo "<h3>Configure LibreNMS API Url and Key</h3><br>";
    echo "<div><form method='post' enctype='multipart/form-data' action='config.php'>
        <p>API key 
        <input type='text' name='api-key' placeholder='API key' value='$apiKey'></p><br>
        <p>Server IP 
        <input type='text' name='server_ip' placeholder='server address' value='$serverip'></p><br>
        <input type='hidden' name='_glpi_csrf_token' value='$token'>
        <button type='submit' class='save' name='save'><p>Save</p></button>
    </form></div>";
    echo "<div>
        <form method='post'>
        <button class='save' name='testconnection' type='submit'><p>Test connection to server</p></button>
        <input type='hidden' name='_glpi_csrf_token' value='$token'>
    </form>
        
        
        </div>";

    if (isset($_POST["save"])) {
        save($_POST["api-key"], $_POST["server_ip"]);
    }
} else {
    Html::helpHeader("NetDiscovery", $_SERVER['PHP_SELF']);
    echo "siamo qui";
}

Html::footer();

function save($apiKey, $serverip) {
    global $DB;

    $DB->query("delete from glpi_plugin_netdiscovery");

    $DB->insert(
            "glpi_plugin_netdiscovery",
            [
                "api_key" => $apiKey,
                "server_ip" => $serverip
            ]
    );
    ApiConfig::getInstance()->restoreData();
    echo "<h1>Configurazione aggiornata</h1>";
}
