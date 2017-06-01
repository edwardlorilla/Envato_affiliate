<?php
require 'vendor/autoload.php';
ob_start();
// Prepare database conectiom
ORM::configure(array(
    'connection_string' => sprintf('mysql:dbname=%s;host=%s', DB_NAME, DB_HOST),
    'username' => DB_USER,
    'password' => DB_PASS
));
ORM::configure('error_mode', PDO::ERRMODE_WARNING);
// Prepare app
$app = new \Slim\Slim(array(
    'debug' => DEBUG_MODE,
    'mode' => 'development',
    'templates.path' => 'views/default'
));

$app->notFound(function () use ($app) {
    $app->render('elements/404.twig');
});

// Prepare view
$app->view(new \Slim\Views\Twig());
$app->view->parserOptions    = array(
    'charset' => 'utf-8',
    'cache' => realpath('cache/twig'),
    'auto_reload' => true,
    'strict_variables' => false,
    'autoescape' => true
);
$app->view->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
    new Twig_Extensions_Extension_Text(),
    new Twig_Extensions_Extension_I18n()
);
// Added session cookie manager middleware
$app->add(new \Slim\Middleware\SessionCookie(array(
    'expires' => '20 minutes',
    'path' => '/',
    'domain' => null,
    'secure' => false,
    'httponly' => false,
    'name' => 'slim_session',
    'secret' => 'CHANGE_ME',
    'cipher' => MCRYPT_RIJNDAEL_256,
    'cipher_mode' => MCRYPT_MODE_CBC
)));
// Define firephp resource
$app->container->singleton('firephp', function () {
    $firephp = FirePHP::getInstance(true);
    // Set firephp debug mode
    $firephp->setEnabled(DEBUG_MODE);

    return $firephp;
    });
// Define envato resource
$app->container->singleton('envato', function () {
    $envato = new Envato_marketplaces();
    $envato->set_cache_dir(realpath('cache/envato'));

    return $envato;
    });
// Define simplepie resource
$app->container->singleton('simplepie', function () {
    if (realpath('cache/simplepie') == false) {
        mkdir(realpath('cache') . '/simplepie', 0777);
        }
    $simplepie = new SimplePie();
    $simplepie->set_cache_location(realpath('cache/simplepie'));
    $simplepie->set_cache_duration(60 * 60 * 24);
    $simplepie->enable_cache(true);

    return $simplepie;
    });
// Define stash cache resource
$app->container->singleton('stash', function () {
    if (realpath('cache/stash') == false) {
        mkdir(realpath('cache') . '/stash', 0777);
        }
    // Create Driver with default options
    $stashFileSystem = new Stash\Driver\FileSystem(array(
        'path' => realpath('cache/stash')
    ));
    // Create the actual cache object, injecting the backend
    $stash           = new Stash\Pool($stashFileSystem);

    return $stash;
    });
// Update category item_count
$db = ORM::get_db();
$db->exec("update category c join (select category_id, count(*) as total from item group by category_id) as t on c.id = t.category_id set item_count = t.total");
$db->exec("DELETE FROM `item` WHERE(`title` IS NULL OR `title` = '')");
$db->exec("DELETE FROM `author` WHERE(`username` IS NULL OR `username` = '')");
function clear_cached()
    {
    array_map('unlink', glob("cache/envato/item*"));
    array_map('unlink', glob("cache/envato/user*"));
    }
clear_cached();
