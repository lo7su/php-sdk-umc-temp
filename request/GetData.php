<?php
require('../vendor/autoload.php');

header('Content-Type: application/json');

use ANZ\BitUmc\SDK\Service\Builder\ClientBuilder;
use ANZ\BitUmc\SDK\Service\Factory\ServiceFactory;

$client = ClientBuilder::init()
    ->setLogin('login')
    ->setPassword('password')
    ->setHttps(false)
    ->setAddress('111.11.11.11')
    ->setBaseName('UMC_BASE')
    ->build();

$factory = new ServiceFactory($client);
$reader  = $factory->getReader();
$clinics = $reader->getClinics()->getData();

if (count($clinics) == 1) {
    foreach ($clinics as $key) {
        $mainClinicUid =  $key['uid'];
        break;
    }
}

$employees = $reader->getEmployees()->getData();
$emaplyeesUid = [];
foreach ($employees as $key => $employee) {
    $employeesArray[] = $employee["uid"];
}

$nomenclature = $reader->getNomenclature($mainClinicUid)->getData();

$schedule = $reader->getSchedule(40, $mainClinicUid, $emaplyeesUid)->getData();

$data = [
    'clinics'      => $clinics,
    'employees'    => $employees,
    'nomenclature' => $nomenclature,
    'schedule'     => $schedule
];


echo(json_encode($data));