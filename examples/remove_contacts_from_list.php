<?php
use \C0chett0\CopacelEws\SynchroExchange;


require_once '../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$id = SynchroExchange::createList('Mailing List With Members', [
    ['email' => 'test@mail.fr', 'name' => 'test'],
    ['email' => 'test2@mail.com', 'name' => 'Mon nom']
]);

echo SynchroExchange::removeContactsFromList($id, [
    'test2@mail.com'
]);