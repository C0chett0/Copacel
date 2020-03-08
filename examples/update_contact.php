<?php
use \C0chett0\CopacelEws\SynchroExchange;


require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$id = SynchroExchange::createContact([
    'firstname' => 'Lolo',
    'lastname' => 'Cochetto',
    'email' => 'Cochetto75@gmail.com',
    'address' => '12 rue Martin Bernard, 75013, Paris, France',
    'company' => 'himself',
    'fix' => '0145659980',
    'mobile' => '0695559980',
    'factory' => 'Les Boulangeries',
    'job' => 'Développeur de génie'
]);

/**
 * Can set up a new value for an exisiting field,
 * Remove an existing value by passing the field name with empty value
 * Or set a previously empty field
 */
echo SynchroExchange::updateContact($id, [
    'firstname' => 'Laurent',
    'lastname' => 'Cochet',
    'job' => '',
    'fax' => '0123456789'
]);