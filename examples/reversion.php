<?php

use Greenter\Model\Company\Address;
use Greenter\Model\Company\Company;
use Greenter\Model\Voided\Reversion;
use Greenter\Model\Voided\VoidedDetail;
use Greenter\Ws\Services\SunatEndpoints;

require __DIR__ . '/../vendor/autoload.php';

// Emisor
$address = new Address();
$address->setUbigueo('150101')
    ->setDepartamento('LIMA')
    ->setProvincia('LIMA')
    ->setDistrito('LIMA')
    ->setUrbanizacion('NONE')
    ->setDireccion('AV LS');

$company = new Company();
$company->setRuc('20000000001')
    ->setRazonSocial('EMPRESA SAC')
    ->setNombreComercial('EMPRESA')
    ->setAddress($address);


$detial1 = new VoidedDetail();
$detial1->setTipoDoc('20')
    ->setSerie('R001')
    ->setCorrelativo('02132132')
    ->setDesMotivoBaja('ERROR DE SISTEMA');

$detial2 = new VoidedDetail();
$detial2->setTipoDoc('20')
    ->setSerie('R001')
    ->setCorrelativo('123')
    ->setDesMotivoBaja('ERROR DE RUC');

$reversion = new Reversion();
$reversion->setCorrelativo('001')
    ->setFecComunicacion(new DateTime())
    ->setFecGeneracion(new DateTime())
    ->setCompany($company)
    ->setDetails([$detial1, $detial2]);


// Envio a SUNAT.
$see = new \Greenter\See();
$see->setService(SunatEndpoints::RETENCION_BETA);
$see->setCertificate(file_get_contents(__DIR__.'/../resources/cert.pem'));
$see->setCredentials('20000000001MODDATOS', 'moddatos');

$res = $see->send($reversion);

if ($res->isSuccess()) {
    /**@var $res \Greenter\Model\Response\SummaryResult*/
    $ticket = $res->getTicket();

    $status = new \Greenter\Ws\Services\ExtService();
    $client = new Greenter\Ws\Services\SoapClient();
    $client->setCredentials('20000000001MODDATOS', 'moddatos');
    $client->setService(SunatEndpoints::RETENCION_BETA);
    $status->setClient($client);
    $result = $status->getStatus($ticket);
    if ($result->isSuccess()) {
        $cdr = $result->getCdrResponse();

        echo '<h2>Respuesta SUNAT:</h2><br>';
        echo '<b>ID:</b> ' . $cdr->getId().'<br>';
        echo '<b>CODE:</b> ' . $cdr->getCode().'<br>';
        echo '<b>DESCRIPTION:</b> ' . $cdr->getDescription().'<br>';
    } else {
        var_dump($result->getError());
    }
} else {
    var_dump($res->getError());
}