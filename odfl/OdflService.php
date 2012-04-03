<?php

class OdflService {
    private $username = "abbcoboom";
    private $pass     = "cchase";

    private $soapClient;
    private $wsdl = "https://www.odfl.com/wsRate_v1/services/ODRate?wsdl";

    private $packageInfo = array();
    private $shipment    = array();

    function __construct() {
        $this->soapClient = new SoapClient($this->wsdl);
    }

    public function makeRequest($args) {

        $this->setPackageInfo($args['item']);

        $this->setShipment($args['recip']);

    }

    public function setPackageInfo($args) {

        $packageInfo = array(
                                   'nmfcClass'=>'65',
                                   'weight'=>'',
                                   'length'=>'',
                                   'width'=>'',
                                   'height'=>'',
                                   'handlingUnits'=>'',
                                   'density'=>''
                                  );

        $this->packageInfo = array_merge($packageInfo,$args);
    }

    private function getPackageInfo() {

        return $this->packageInfo;

    }

    public function setShipment($args) {

        $shipment = array(
                                'originPostalCode'=>'32920',
                                'originCountry'=>'usa',
                                'destinationPostalCode'=>'',
                                'destinationCountry'=>'usa',
                                'requestorType'=>'S',
                                'odfl4meUser'=>$this->username,
                                'odfl4mePassword'=>$this->pass,
                                'odflCustomerAccount'=>'*****',
                                'freightArray'=>$this->getPackageInfo(),
                                'mexicoServiceCenter'=>'',
                                'currencyFormat'=>'',
                                'requestReferenceNumber'=>''
                               );

        $this->shipment = array_merge($shipment, $args);
    }

    public function getShipment() {

        return $this->shipment;

    }

    public function makeCall($function, $args) {

        return  $this->soapClient->__soapCall($function, array($args));

    }

    public function returnResponse() {

        return $this->makeCall("getRateEstimate",$this->getShipment());

    }
}
