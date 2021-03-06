<?php

require_once __DIR__ . '/vendor/autoload.php';

use Chadicus\Books\FileRepository;
use Chadicus\Slim\OAuth2\Routes;
use Chadicus\Slim\OAuth2\Middleware;
use Slim\Http;
use Slim\Views;
use OAuth2\Storage;
use OAuth2\GrantType;

$pdo = new \PDO('sqlite:' . __DIR__ . '/slim_oauth2.db');

$storage = new Storage\Pdo($pdo);

$server = new OAuth2\Server(
    $storage,
    [
        'access_lifetime' => 3600,
    ],
    [
        new GrantType\ClientCredentials($storage),
        new GrantType\AuthorizationCode($storage),
    ]
);

$app = new Slim\App([]);

$container = $app->getContainer();
$container['books-respository'] = function ($c) {
    return new FileRepository(__DIR__ . '/books.json');
};

$renderer = new Views\PhpRenderer( __DIR__ . '/vendor/chadicus/slim-oauth2-routes/templates');

$app->map(['GET', 'POST'], Routes\Authorize::ROUTE, new Routes\Authorize($server, $renderer))->setName('authorize');
$app->post(Routes\Token::ROUTE, new Routes\Token($server))->setName('token');
$app->map(['GET', 'POST'], Routes\ReceiveCode::ROUTE, new Routes\ReceiveCode($renderer))->setName('receive-code');

$authorization = new Middleware\Authorization($server, $app->getContainer());

$app->get('/books', '\\Chadicus\\Books\\BooksController:index')->setName('books-search')->add($authorization);
$app->get('/books/{id}', '\\Chadicus\\Books\\BooksController:get')->setName('books-detail')->add($authorization);
$app->post('/books', '\\Chadicus\\Books\\BooksController:post')->setName('book-create')->add($authorization->withRequiredScope(['bookCreate']));

$app->run();
