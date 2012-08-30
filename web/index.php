<?php
/*
   web/index.php
 */
require_once __DIR__.'/../vendor/autoload.php';

use api\route\routeManager;
use api\language\languageUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Monolog\Logger;

$app = new Silex\Application();

$env = getenv('APP_ENV') ?: 'prod';
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.json"));

// definitions
#$app['url_service'] = function() {
#	return new routeManager();
#};
#echo "<pre>";
#print_r($app['databases']);
#echo "</pre>";
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
	'dbs.options' =>$app['databases']
));

if(isset($_SERVER["HTTPS"])) {
	$ssl = "on";
} else { 
	$ssl = "off";
}
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());


$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../themes/default/',
));
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) use ($ssl) {
    $twig->addGlobal('ssl', $ssl);
    return $twig;
}));

$app->register(new Silex\Provider\SecurityServiceProvider(), array(
	'security.firewalls' => $app['firewalls'],
));

$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
    'http_cache.cache_dir' => __DIR__.'/../cache/',
));
Request::trustProxyData();


$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => __DIR__.'/../logs/'. $env . '.log',
    'monolog.level' => Logger::DEBUG
));

use Symfony\Component\Translation\Loader\YamlFileLoader;

$app->register(new Silex\Provider\TranslationServiceProvider(), array(
    'locale_fallback' => 'en',
));

$app['translator'] = $app->share($app->extend('translator', function($translator, $app) {
    $translator->addLoader('yaml', new YamlFileLoader());

    $translator->addResource('yaml', __DIR__.'/../locales/en.yml', 'en');
    $translator->addResource('yaml', __DIR__.'/../locales/de.yml', 'de');
    $translator->addResource('yaml', __DIR__.'/../locales/fr.yml', 'fr');

    return $translator;
}));
$app->register(new Silex\Provider\SessionServiceProvider());

use Silex\Provider\FormServiceProvider;
$app->register(new FormServiceProvider());
$app->register(new Silex\Provider\ValidatorServiceProvider());

$app['route_Manager'] = $app->share(function ($app) {
    return new routeManager($app);
    });


use api\Security\User\CluddyUserProvider;
$app['users'] = $app->share(function ($app) {
		return new CluddyUserProvider($app['db']);
});

$app['users']->checkDatabase();
use api\Security\Authentication\Provider\CluddySecurityProvider;
use api\Security\Firewall\CluddySecurityListener;
$app['security.authentication_listener.factory.cluddysecurity'] = $app->protect(function ($name, $options) use ($app) {
		// define the authentication provider object
		$app['security.authentication_provider.'.$name.'.cluddy'] = $app->share(function () use ($app) {
			return new CluddySecurityProvider($app['users'], __DIR__.'/security_cache');
			});

		// define the authentication listener object
		$app['security.authentication_listener.'.$name.'.cluddy'] = $app->share(function () use ($app) {
			return new CluddySecurityListener($app['security'], $app['security.authentication_manager'], $app);
			});

		return array(
			// the authentication provider id
			'security.authentication_provider.'.$name.'.cluddy',
			// the authentication listener id
			'security.authentication_listener.'.$name.'.cluddy',
			// the entry point id
			null,
			// the position of the listener in the stack
			'pre_auth'
			);
});

$routeManager = $app['route_Manager']->boot();



#$sm = $app['db']->getSchemaManager();
#echo "<pre>";
//var_dump($sm->createSchema());
//$bases = $sm->listDatabases();
#$fromSchema = $sm->createSchema();
#$toSchema = clone $fromSchema;
#$toSchema->dropTable('plop');
#$sql = $fromSchema->getMigrateToSql($toSchema, $sm->getDatabasePlatform());
#print_r($sql);
#foreach ($sql as $sql_line) {
#$result = $app['db']->executeQuery($sql_line);
#}
//$plop = $app['db']->fetchAssoc('SELECT * FROM table');
//var_dump($plop);
/** App Definition */
#$urls = array(
#		'goog' => 'http://www.google.com',
#		'fb' => 'http://www.facebook.com',
#	     );
//$app->get('/{url_slug}',function($url_slug) use($app, $urls){
//		return $app->redirect($app['url_service']->get($url_slug));
//		});
if ($app['debug']) {
	$app->run();
 }
 else{
	 $app['http_cache']->run();
 }
?>
