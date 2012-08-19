<?php
/*
   web/index.php
 */
require_once __DIR__.'/../vendor/autoload.php';
#require_once __DIR__.'/../src/api/route/routeManager.php';
use api\route\routeManager;

$app = new Silex\Application();

$env = getenv('APP_ENV') ?: 'prod';
$app->register(new Igorw\Silex\ConfigServiceProvider(__DIR__."/../config/$env.json"));

// definitions
$app['url_service'] = function() {
	return new routeManager();
};
echo "<pre>";
print_r($app['databases']);
echo "</pre>";
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
	'dbs.options' =>$app['databases']
));

$sm = $app['db']->getSchemaManager();
echo "<pre>";
//var_dump($sm->createSchema());
//$bases = $sm->listDatabases();
$fromSchema = $sm->createSchema();
$toSchema = clone $fromSchema;
$toSchema->dropTable('plop');
$sql = $fromSchema->getMigrateToSql($toSchema, $sm->getDatabasePlatform());
print_r($sql);
foreach ($sql as $sql_line) {
$result = $app['db']->executeQuery($sql_line);
}
//$plop = $app['db']->fetchAssoc('SELECT * FROM table');
//var_dump($plop);
/** App Definition */
$urls = array(
		'goog' => 'http://www.google.com',
		'fb' => 'http://www.facebook.com',
	     );
//$app->get('/{url_slug}',function($url_slug) use($app, $urls){
//		return $app->redirect($app['url_service']->get($url_slug));
//		});
//$app->run();
?>
