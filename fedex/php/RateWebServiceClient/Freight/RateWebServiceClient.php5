<?php

// Copyright 2009, FedEx Corporation. All rights reserved.
// Version 9.0.0

require_once('../../library/fedex-common.php5');

$newline = "<br />";
//The WSDL is not included with the sample code.
//Please include and reference in $path_to_wsdl variable.
$path_to_wsdl = "../../wsdl/RateService_v10.wsdl";

ini_set("soap.wsdl_cache_enabled", "0");
 
$client = new SoapClient($path_to_wsdl, array('trace' => 1)); // Refer to http://us3.php.net/manual/en/ref.soap.php for more information

var_dump(getProperty('key'));
var_dump(getProperty('password'));
var_dump(getProperty('freightaccount'));
var_dump(getProperty('meter'));

echo "<pre>";
print_r(getProperty('freightbilling'));
echo "</pre>";
$request['WebAuthenticationDetail'] = array(
	'UserCredential' =>array(
		'Key' => getProperty('key'), 
		'Password' => getProperty('password')
	)
); 
$request['ClientDetail'] = array(
	'AccountNumber' => getProperty('freightaccount'), 
	'MeterNumber' => getProperty('meter')
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
//$request['RequestedShipment']['ServiceType'] = 'INTERNATIONAL_PRIORITY'; // valid values STANDARD_OVERNIGHT, PRIORITY_OVERNIGHT, FEDEX_GROUND, ...
$request['RequestedShipment']['PackagingType'] = 'YOUR_PACKAGING'; // valid values FEDEX_BOX, FEDEX_PAK, FEDEX_TUBE, YOUR_PACKAGING, ...
$request['RequestedShipment']['Shipper'] = getProperty('freightbilling');
$request['RequestedShipment']['Recipient'] = addRecipient();
$request['RequestedShipment']['ShippingChargesPayment'] = addShippingChargesPayment();
$request['RequestedShipment']['FreightShipmentDetail'] = array(
	'FedExFreightAccountNumber' => getProperty('freightaccount'),
	'FedExFreightBillingContactAndAddress' => getProperty('freightbilling'),
//	'PrintedReferences' => array(
//		'Type' => 'SHIPPER_ID_NUMBER',
//		'Value' => 'RBB1057'
//	),
	'Role' => 'SHIPPER',
	'PaymentType' => 'PREPAID',
	'CollectTermsType' => 'STANDARD',
	'DeclaredValuePerUnit' => array(
		'Currency' => 'USD',
		'Amount' => 50
	),
	'LiabilityCoverageDetail' => array(
		'CoverageType' => 'NEW',
		'CoverageAmount' => array(
			'Currency' => 'USD',
			'Amount' => '50'
		)
	),
	'TotalHandlingUnits' => 15,
	'ClientDiscountPercent' => 0,
	'PalletWeight' => array(
		'Units' => 'LB',
		'Value' => 20
	),
	'ShipmentDimensions' => array(
		'Length' => 180,
		'Width' => 93,
		'Height' => 106,
		'Units' => 'IN'
	),
	'LineItems' => array(
		'FreightClass' => 'CLASS_050',
		'ClassProvidedByCustomer' => false,
		'HandlingUnits' => 15,
		'Packaging' => 'PALLET',
		'BillOfLaddingNumber' => 'BOL_12345',
		'PurchaseOrderNumber' => 'PO_12345',
		'Description' => 'Heavy Stuff',
		'Weight' => array(
			'Value' => 50.0,
			'Units' => 'LB'
		),
		'Dimensions' => array(
			'Length' => 180,
			'Width' => 93,
			'Height' => 106,
			'Units' => 'IN'
		),
		'Volume' => array(
			'Units' => 'CUBIC_FT',
			'Value' => 30
		)
		
	)
	
);
$request['RequestedShipment']['RateRequestTypes'] = 'ACCOUNT'; 
$request['RequestedShipment']['RateRequestTypes'] = 'LIST'; 
$request['RequestedShipment']['PackageCount'] = '1';

try 
{
	if(setEndpoint('changeEndpoint'))
	{
		$newLocation = $client->__setLocation(setEndpoint('endpoint'));
	}
	
	$response = $client ->getRates($request);
        
    if ($response -> HighestSeverity != 'FAILURE' && $response -> HighestSeverity != 'ERROR')
    {  	
    	$rateReply = $response -> RateReplyDetails;
    	echo '<table border="1">';
    	echo '<tr><th>Rate Details</th><th>&nbsp;</th></tr>';
        trackDetails($rateReply, '');
		echo '</table>';
       
        printSuccess($client, $response);
    }
    else
    {
        printError($client, $response);
    } 
    
    writeToLog($client);    // Write to log file   

} catch (SoapFault $exception) {
   printFault($exception, $client);        
}

function addRecipient(){
	$recipient = array(
		'Contact' => array(
			'PersonName' => 'Sender Name',
			'CompanyName' => 'Sender Company Name',
			'PhoneNumber' => '1234567890'),
		'Address' => array(
			'StreetLines' => array('Address Line 1'),
			'City' => 'Austin',
			'StateOrProvinceCode' => 'TX',
			'PostalCode' => '73301',
			'CountryCode' => 'US')
	);
	return $recipient;
}

function addShippingChargesPayment(){
	$shippingChargesPayment = array(
		'PaymentType' => 'SENDER', // valid values RECIPIENT, SENDER and THIRD_PARTY
		'Payor' => array(
			'AccountNumber' => getProperty('shipaccount'),
			'CountryCode' => 'US')
	);
	return $shippingChargesPayment;
}
?>
