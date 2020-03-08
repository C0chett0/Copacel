<?php
use \C0chett0\CopacelEws\SynchroExchange;


require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$id = SynchroExchange::createList('Mailing List With Members', [
    'test@mail.fr',
    'test2@mail.com'
]);

echo SynchroExchange::removeContactsFromList($id, [
    'test2@mail.com'
]);