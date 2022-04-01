<?php

class Port implements Countable
{
    var $name;
    var $mac;
    var $switchHostname;
    var $glpiNetworkDeviceId;
    var $connectedTo = [];
    var $down;
    var $status;
    var $portId;
    var $uplink;
    var $vlan;
    var $glpiPortid;

    static function networkDevicePort($name, $portId, $mac, $down, $status, $switchHostname, $glpiNetworkDeviceId): Port
    {
        $port = new self();
        $port->mac = self::parseMac($mac);
        $port->switchHostname = $switchHostname;
        $port->portId = $portId;
        $port->name = $name;
        $port->status = $status;
        $port->down = $down;
        $port->uplink = false;
        $port->glpiNetworkDeviceId = $glpiNetworkDeviceId;
        $port->findGlpiId(true);
        $port->findConnectedDevices();

        return $port;
    }

    static function endDevicePort($mac): Port
    {
        $port = new self();
        $port->mac = self::parseMac($mac);
        $port->findGlpiId(false);
        return $port;
    }

    private function findGlpiId($networkDevicePort)
    {
        if(isset($this->glpiNetworkDeviceId) && $networkDevicePort)
        {
            $port = new NetworkPort();
            $fields = $port->find("items_id = '" . $this->glpiNetworkDeviceId . "' and name = '" . $this->name . "'");
            foreach ($fields as $field)
            {
                if(isset($field["id"]))
                {
                    $this->glpiPortid = $field["id"];
                    break;
                }
            }
        }
        else
        {
            if(isset($this->mac))
            {

                $port = new NetworkPort();
                $fields = $port->find("mac = '" . $this->mac . "'", [], 1);
                foreach ($fields as $field)
                {
                    if(isset($field["id"]))
                    {
                        $this->glpiPortid = $field["id"];
                        $this->glpiNetworkDeviceId = $field["items_id"];
                        break;
                    }
                }
            }
        }
    }

    private function findConnectedDevices()
    {
        if (!empty(ApiConfig::getInstance()->executeQuery("resources/fdb")["ports_fdb"])) {
            $jsonFdb = ApiConfig::getInstance()->executeQuery("resources/fdb")["ports_fdb"];
        }
        foreach ($jsonFdb as $key => $value)
        {
            if($value["port_id"] == $this->portId)
            {
                $mac = $value["mac_address"];
                $this->connectedTo[] = self::endDevicePort($mac);
            }
        }
    }

    public function isUpLink($hostnames)
    {
        $uplinkPorts = ApiConfig::getInstance()->executeQuery("resources/links")["links"];
        foreach ($uplinkPorts as $key=> $value)
        {
            if($value["local_port_id"] == $this->portId)
            {
                $connectedTo = strtolower($value["remote_hostname"]);
                if(in_array($connectedTo, $hostnames))
                {
                    $this->uplink = true;
                }
            }
        }
    }

    public static function parseMac($mac): string
    {
        $newMac = substr_replace($mac, ":",2,0);
        $newMac = substr_replace($newMac, ":",5,0);
        $newMac = substr_replace($newMac, ":",8,0);
        $newMac = substr_replace($newMac, ":",11,0);
        $newMac = substr_replace($newMac, ":",14,0);
        return $newMac;
    }

    public function count()
    {
        // TODO: Implement count() method.
    }
}