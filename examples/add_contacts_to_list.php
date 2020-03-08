<?php
use \C0chett0\CopacelEws\SynchroExchange;


require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$id =  SynchroExchange::createList('Mailing List With Members');

echo SynchroExchange::addContactsToList($id, [
    'test@mail.fr',
    'test2@mail.com'
]);