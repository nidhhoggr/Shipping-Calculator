<?php

// Copyright 2009, FedEx Corporation. All rights reserved.
// Version 9.0.0


require_once(dirname(__FILE__) . '/../../library/Library.php');


class FedexService {

    private $path_to_wsdl = "/../../wsdl/RateService_v10.wsdl";
    private $client;
    private $library;
    private $args;
    private $request;

    public function makeRequest($args) {

        $currpath = dirname(__FILE__);

        ini_set("soap.wsdl_cache_enabled", "0");

        $this->args = $args;
        $this->client = new SoapClient($currpath . $this->path_to_wsdl, array('trace' => 1));
        $this->library = new Library();
        $this->initVar();
        $this->calculate();
    } 

    private function debugCreds() {
        $lib = $this->library;
        var_dump($lib->getProperty('key'));
        var_dump($lib->getProperty('password'));
        var_dump($lib->getProperty('shipaccount'));
        var_dump($lib->getProperty('meter'));
    }

    private function initVar() {
        $request = &$this->request;
 
        $request['WebAuthenticationDetail'] = array(
	    'UserCredential' =>array(
	    	    'Key' => $this->library->getProperty('key'), 
		    'Password' => $this->library->getProperty('password')
	    )
        ); 
        $request['ClientDetail'] = array(
	    'AccountNumber' => $this->library->getProperty('shipaccount'), 
	    'MeterNumber' => $this->library->getProperty('meter')
        );
        $request['TransactionDetail'] = array('CustomerTransactionId' => ' *** Rate Request v10 using PHP ***');
        $request['Version'] = array(
	    'ServiceId' => 'crs', 
	    'Major' => '10', 
    	    'Intermediate' => '0', 
	    'Minor' => '0'
        );
        $request['ReturnTransitAndCommit'] = true;
        $request['RequestedShipment']['DropoffType'] = 'REGULAR_PICKUP'; // valid values REGULAR_PICKUP, REQUEST_COURIER, ...
        $request['RequestedShipment']['ShipTimestamp'] = date('c');
        $request['RequestedShipment']['ServiceType'] = 'FEDEX_GROUND'; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
        $request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
//$request['RequestedShipment']['TotalInsuredValue']=array('Ammount'=>100,'Currency'=>'USD');
        $request['RequestedShipment']['Shipper'] = $this->library->getProperty('freightbilling');
        $request['RequestedShipment']['Recipient'] = $this->addRecipient();
        $request['RequestedShipment']['ShippingChargesPayment'] = $this->addShippingChargesPayment();
        $request['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT'; 
        $request['RequestedShipment']['RateRequestTypes'] = 'LIST'; 
        $request['RequestedShipment']['PackageCount'] = '1';
        $request['RequestedShipment']['RequestedPackageLineItems'] = $this->addPackageLineItem1();
    }


    public function calculate() {
        $lib = $this->library;
        $request = $this->request;
        try 
        {
	    if($lib->setEndpoint('changeEndpoint')) {
		$newLocation = $this->client->__setLocation($lib->setEndpoint('endpoint'));
	    }

	    $this->response = $this->client->getRates($request);

            $lib->writeToLog($this->client);    // Write to log file   

         } catch (SoapFault $exception) {
             $lib->printFault($exception, $this->client);        
         }
    }

    public function returnResponse() {

        $rateReply = $this->response->RateReplyDetails;

        $amount = number_format($rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",");

        if(array_key_exists('DeliveryTimestamp',$rateReply)){
            $date= $rateReply->DeliveryTimestamp;
        }else if(array_key_exists('TransitTime',$rateReply)){
            $date= $rateReply->TransitTime;
        }else {
            $date=null;
        }

        return array('amount'=>$amount,'date'=>$date);
    }

    public function displayResponse() {
        $reponse = $this->response;

        if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR') {
            $rateReply = $response->RateReplyDetails;
            echo '<table border="1">';
            echo '<tr><td>Service Type</td><td>Amount</td><td>Delivery Date</td></tr><tr>';
            $serviceType = '<td>'.$rateReply -> ServiceType . '</td>';
            $amount = '<td>$' . number_format($rateReply->RatedShipmentDetails[0]->ShipmentRateDetail->TotalNetCharge->Amount,2,".",",") . '</td>';
            if(array_key_exists('DeliveryTimestamp',$rateReply)){
                $deliveryDate= '<td>' . $rateReply->DeliveryTimestamp . '</td>';
            }else if(array_key_exists('TransitTime',$rateReply)){
                $deliveryDate= '<td>' . $rateReply->TransitTime . '</td>';
            }else {
                $deliveryDate='<td>&nbsp;</td>';
            }
            echo $serviceType . $amount. $deliveryDate;
            echo '</tr>';
            echo '</table>';

            $lib->printSuccess($this->client, $response);
        }
        else {
            $lib->printError($this->client, $response);
        }
    }

    function addRecipient(){
        $args = $this->args['recip'];

	$recipient = array(
		'Contact' => array(
			'PersonName'  => $args['person_name'],
			'CompanyName' => $args['company_name'],
			'PhoneNumber' => $args['phone_number']
		),
		'Address' => array(
			'StreetLines' => array($args['address']),
			'City' => $args['city'],
			'StateOrProvinceCode' => $args['state'],
			'PostalCode' => $args['zip'],
			'CountryCode' => $args['country'],
			'Residential' => $args['residential']
                )
	);
	return $recipient;	                                    
    }

    function addShippingChargesPayment(){
	$shippingChargesPayment = array(
		'PaymentType' => 'SENDER', // valid values RECIPIENT, SENDER and THIRD_PARTY
		'Payor' => array(
			'AccountNumber' => $this->library->getProperty('shipaccount'),
			'CountryCode' => 'US')
	);
	return $shippingChargesPayment;
    }

    function addPackageLineItem1() {
        $args = $this->args['item'];
	
        $packageLineItem = array(
		'SequenceNumber'=>1,
		'GroupPackageCount'=>$args['qty'],
		'Weight' => array(
			'Value' => $args['weight'],
			'Units' => 'LB'
/*
		),
		'Dimensions' => array(
			'Length' => 108,
			'Width' => 5,
			'Height' => 5,
			'Units' => 'IN'
*/
		)
        );
	
        return $packageLineItem;
    }
}
