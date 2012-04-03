<?php

class Service {

/*Author: Joseph Persie
  Description: Factory Pattern to return a rate
*/

    private $odfl_service = "odfl/OdflService.php";
    private $fedex_service = "fedex/php/RateWebServiceClient/Rate/FedexService.php";
    private $service;
    private $response;
    private $zip = "32920";
    private $icebreaker = "150";

    function __construct($args) {
 
       $service = $this->getService($args);
        if(!$service) die("no service specified");

        $class = ucfirst($service);
        $class_path = $service . "_service";
        $class_name = $class."Service";
        $class_parser = "parse". $class . "Response";
        $class_mapper = "mapArgsTo". $class;

        require_once($this->{$class_path});

        $object = new $class_name;

        $args = $this->{$class_mapper}($args);

        $object->makeRequest($args);

        $this->service = $service;

        $this->response = $object->returnresponse();
        echo json_encode($this->{$class_parser}()); 
    }

    private function getLbs($args) {
        return $args['item']['qty'] * $args['item']['weight'];
    }

    private function getService($args) {

        if($this->getLbs($args) < $this->icebreaker)
            return "fedex";
        else
            return "odfl";

    }

    private function parseFedexResponse() {
        return (float) $this->response['amount'];
    }

    private function parseOdflResponse() {
        return (float) $this->response->rateReturn->rateEstimate->netFreightCharge;
    }

    private function mapArgsToOdfl($params) {
        
        $args['item'] = array(
                      'weight'=>$params['item']['weight'],
                      'density'=>''
                     );

        $args['recip'] = array(
                       'originPostalCode'=>$this->zip,
                       'originCountry'=>'usa',
                       'destinationPostalCode'=>$params['recip']['zip'],
                       'destinationCountry'=>'usa',
                       'requestorType'=>'S',
                       'odflCustomerAccount'=>'*****',
                       'mexicoServiceCenter'=>'',
                       'currencyFormat'=>'',
                       'requestReferenceNumber'=>''
                      );

        return $args;

    }
    
    private function mapArgsToFedex($param) {

        $args['item'] = array(
                         'qty'    => $param['item']['qty'],
                         'weight' => $param['item']['weight']
                        );

        $args['recip']   = array(
                         'zip'            => $param['recip']['zip'],
                         'country'        => 'US',
                         'residential'    => false
                        );

        return $args;
    }

    private function debug($debug) {
        echo "<pre>";
        print_r($debug);
        echo "</pre>";
    }

}

$args = array(
              'item'=>array('qty'=>$_GET['qty'],'weight'=>$_GET['weight']),
              'recip'=>array('zip'=>$_GET['zip'])
             );

$s = new Service($args);
