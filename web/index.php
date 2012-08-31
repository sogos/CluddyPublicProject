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
use api\Security\User\CluddyUserProvider;
use Monolog\Handler\StreamHandler;
use api\Logger\CluddyLogger;

$app = new Silex\Application();
$app['logger'] =  $app->share(function () use ($app) {
                return new CluddyLogger($app);
                });


$env = getenv('APP_ENV') ?: 'prod';
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.json"));


$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
			'dbs.options' =>$app['databases']
			));

if(isset($_SERVER["HTTPS"])) {
	$ssl = "on";
} else { 
	$ssl = "off";
}
$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

$app->register(new Silex\Provider\MonologServiceProvider(), array(
			'monolog.logfile' => __DIR__.'/../logs/'. $env . '.log',
			'monolog.level' => Logger::DEBUG,
			'monolog.name' => 'app',
			));


$app->register(new Silex\Provider\TwigServiceProvider(), array(
			'twig.path' => __DIR__.'/../themes/default/',
			));
$app['twig'] = $app->share($app->extend('twig', function($twig, $app) use ($ssl) {
			$twig->addGlobal('ssl', $ssl);
			return $twig;
			}));


$app->register(new Silex\Provider\HttpCacheServiceProvider(), array(
			'http_cache.cache_dir' => __DIR__.'/../cache/',
			));
Request::trustProxyData();



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



$routeManager = $app['route_Manager']->boot();
use api\Security\Authentication\Provider\CluddySecurityProvider;
use api\Security\Firewall\CluddySecurityListener;

$app->register(new Silex\Provider\SecurityServiceProvider());
$app['security.firewalls'] = array(
                'login' => array(
                        'pattern' => '^/login$',
                        ),
                'main' => array(
			'cluddysecurity' => array('login_path' => '_login', 'check_path' => '/login_check'),
                        'pattern' => '^.*$',
                        'users' => $app->share(function () use ($app) {
                                return new CluddyUserProvider($app['db'], $app['logger']);
                                }),
                        ),
                );



$app['security.authentication_listener.factory.cluddysecurity'] = $app->protect(function ($name, $options) use ($app) {
		// define the authentication provider object

		$app['security.authentication_provider.'.$name.'.cluddysecurity'] = $app->share(function () use ($app,$options) {
			return new CluddySecurityProvider($app['security.user_provider.main'], __DIR__.'/../security_cache', $app['logger']);
			});

		// define the authentication listener object
		$app['security.authentication_listener.'.$name.'.cluddysecurity'] = $app->share(function ($options) use ($app) {
			return new CluddySecurityListener($app['security'], $app['security.authentication_manager'], $app,$options);
			});

		return array(
			// the authentication provider id
			'security.authentication_provider.'.$name.'.cluddysecurity',
			// the authentication listener id
			'security.authentication_listener.'.$name.'.cluddysecurity',
			// the entry point id
			null,
			// the position of the listener in the stack
			'pre_auth',
			);
});


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
