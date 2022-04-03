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

include ('../../../inc/includes.php');
include('../inc/networkdevice.class.php');
include ('../inc/computer.class.php');
include ("../inc/logger.class.php");

if ($_SESSION["glpiactiveprofile"]["interface"] == "central") {
    Html::header("NetDiscovery", $_SERVER['PHP_SELF'], "plugins", "pluginexampleexample", "");

    $token = Session::getNewCSRFToken();
    echo "<form method='post'>
        <button class='save' name='execute' type='submit'><p>Run</p></button>
        <input type='hidden' name='_glpi_csrf_token' value='$token'>
    </form>";

    echo "<form method='get' action='exportdata.php'>
        <button class='save' name='export' type='submit'><p>Export as CSV</p></button>
        <input type='hidden' name='_glpi_csrf_token' value='$token'>
    </form>";

    if (isset($_POST["execute"])) {
        echo "<h3>Esecuzione in corso...</h3>";
        execute();
        echo "<h3>Esecuzione completata</h3>";
    }
} else {
    Html::helpHeader("NetDiscovery", $_SERVER['PHP_SELF']);
}

function execute() {
    $jsonDecode = ApiConfig::getInstance()->executeQuery("devices");
    $decodedDevices = $jsonDecode["devices"];
    $hostnames = [];
    $devices = [];

    foreach ($decodedDevices as $jsonDevice) {
        $device = NetworkDevice::createDevice($jsonDevice);
        $devices[] = $device;
        $hostnames[] = $device->sysName;
    }

    // Print devices found in LibreNMS and corresponding GLPI ones
    echo "<h3>List of devices found in LibreNMS</h3> 
            <table><tr><th>SysName</th><th>GlpiID</th>";
    foreach ($devices as $device) {
        echo '<tr><td> ' . $device->sysName . ' </td><td> ' . (($device->glpiID > 0) ? $device->glpiID : 'Not found') . "</td></tr>\n";
    }
    echo "</table>";
    // Search and connect ports
    foreach ($devices as $device) {
        $device->checkUplinkPorts($hostnames);
        foreach ($device->ports as $port) {
            if (isset($port->glpiPortid) && !$port->uplink) {
                echo "<h3>" . $port->name . " on " . $port->switchHostname . "</h3><br>";
                echo "<ul>";
                foreach ($port->connectedTo as $connectedDevice) {
                    if (isset($connectedDevice->glpiPortid)) {
                        $result = NetworkDevice::connect($port, $connectedDevice);
                        switch ($result) {
                            case "insert":
                                echo "<li>ADDED mac: " . $connectedDevice->mac . "</li>";
                                Logger::log($port, $connectedDevice, "insert");
                                break;
                            case "update":
                                echo "<li>update mac: " . $connectedDevice->mac . "</li>";
                                Logger::log($port, $connectedDevice, "update");
                                break;
                            default:
                                echo "<li>skipped mac: " . $connectedDevice->mac . "</li>";
                                Logger::log($port, $connectedDevice, "skip");
                                break;
                        }
                    } else {
                        Logger::log($port, $connectedDevice, "not found");
                    }
                }
                echo "</ul>";
            }
        }
    }
}

//checkTypeRight('PluginExampleExample',"r");
//Search::show('PluginExampleExample');

Html::footer();
