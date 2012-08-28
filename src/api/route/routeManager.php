<?php

namespace api\route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use api\language\languageUtils;



class routeManager {

	public function __construct($app){
	
		$fallback_language = languageUtils::getClientLanguage();
		$app->get('/{locale}/ariane/{module}', function ($locale,$module) use ($app) {
				return $app['twig']->render('ariane.twig', array(
						'module' => $module,
						'_locale' => $locale
						));

				})
		->value('module', 'home')
		->value('locale', $fallback_language)
		->bind('_ariane');


		$app->get('/{locale}/{module}', function ($locale,$module) use ($app) {
				return $app['route_Manager']->get($app,$locale,$module);
				})
		->value('locale', $fallback_language)
		->value('module', 'home');

		$app->get('/{locale}/{module}/{action}', function ($locale,$module,$action) use ($app) {
				return $app['route_Manager']->get($app,$locale,$module,$action);
				})
		->value('locale', $fallback_language)
		->value('action','index');

		$app->get('/{locale}/{module}/{action}/{id}', function ($locale,$module,$action, $id) use ($app) {
				return $app['route_Manager']->get($app,$locale,$module,$action,$id);
				})
		->value('locale', $fallback_language)
		->assert('id', '\d+');


		$app->post('/{locale}/{module}/{action}/{id}', function ($module,$locale,$action, $id) use ($app) {
				return $app['route_Manager']->post($request,$locale, $module,$action,$id);
				})
                ->value('locale', $fallback_language)
		->assert('id', '\d+');
		$app->get('/{locale}/home', function ($locale) use ($app) {
				return $app['route_Manager']->get($app,"home",$locale);
				})
                ->value('locale', $fallback_language)
		->bind('_homepage');



/*	 $app->get('/{slug}', function ($slug) use ($app) {
                        return $app['route_Manager']->get($app['request'],$slug);
                        })
        ->assert('slug', '.+');
*/

		$app->error(function (\Exception $e, $code) use ($app, $fallback_language) {
				$app['translator']->setLocale($locale);
				
				return  new Response($app['twig']->render('error.twig', array(
					'code' => $code,
					'module' => $module,
					'e' => $e
					)), 200, array(
                                                'Cache-Control' => 's-maxage=5, public',
                                                ));

			});

	}



	public function boot() {
	}

	public function get(\Silex\Application $app,$locale,$module = "", $action = "", $id = "") {
		$app['translator']->setLocale($locale);
		$url = "";
		if(!empty($module)) {
			$url .= '/'. $module;
		}
		if(!empty($action)) {
			$url .= '/'. $module;
		}
		if(!empty($id)) {
			$url .= '/'. $id;
		}
		$app['monolog']->addWarning(sprintf(">> Calling Route: " . $module . ":" . $action . ":". $id));
			return new Response($app['twig']->render('debug.twig', array(
						'module' => $module,
						'action' => $action,
						'id' => $id,
						'url' => $url
						)), 200, array(
						'Cache-Control' => 's-maxage=5, public',
						
						));
	}
	
	public function post($request ,$module = "", $action = "", $id = "" ) {
	}
		
}

?>
