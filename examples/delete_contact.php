<?php
use \C0chett0\CopacelEws\SynchroExchange;


require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$id =  SynchroExchange::createContact([
    'firstname' => 'Laurent',
    'lastname' => 'Cochet',
    'email' => 'Cochetto75@gmail.com',
    'address' => '12 rue Martin Bernard, 75013, Paris, France',
    'company' => 'himself',
    'fix' => '0145659980',
    'mobile' => '0695559980',
    'factory' => 'Les Boulangeries',
    'job' => 'Développeur de génie'
]);

echo SynchroExchange::deleteContact($id);