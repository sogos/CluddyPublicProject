<?php

namespace api\route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use api\language\languageUtils;
use Symfony\Component\Validator\Constraints as Assert;


class routeManager {

	public function http_fetch_url($url, $timeout = 10, $userpwd = '', $maxredirs = 10)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_MAXREDIRS, $maxredirs);
		if ($userpwd) {
			curl_setopt($ch, CURLOPT_USERPWD, $userpwd);
		}
		$data = curl_exec($ch);
		curl_close($ch);

		return $data;
	}

	public function __construct($app){

		$fallback_language = languageUtils::getClientLanguage();
		$session_language = $app['session']->get('language');
		if(!empty($session_language)) {
			$fallback_language = $session_language;
		}
		$app->get('/{locale}/ariane/{module}', function ($locale,$module) use ($app) {
				return $app['twig']->render('ariane.twig', array(
						'module' => $module,
						'_locale' => $locale
						));

				})
		->value('module', 'home')
			->value('locale', $fallback_language)
			->bind('_ariane');

		$facebook_app_id = getenv('facebook_app_id');
		$facebook_secret_id = getenv('facebook_secret_id');
		$app->get('/{locale}/facebook_check', function ($locale) use ($app, $facebook_app_id) {
				$facebook_uniq = uniqid();
				$app['session']->set('facebook_uniq', $facebook_uniq);
				return $app->redirect(' https://www.facebook.com/dialog/oauth?client_id=' . $facebook_app_id . '&redirect_uri=https://www.cluddy.fr/' .$locale .'/facebook_step2&scope=user_about_me,email&state=' . $facebook_uniq);
				})
		->value('locale', $fallback_language);

		$app->get('/{locale}/facebook_step2', function (Request $request,$locale) use ($app, $facebook_app_id, $facebook_secret_id) {
				$facebook_uniq = uniqid();
				$facebook_code = $request->get("code");
				$token_url = 'https://graph.facebook.com/oauth/access_token?client_id=' . $facebook_app_id . '&redirect_uri=https://www.cluddy.fr/' .$locale .'/facebook_step2&client_secret='. $facebook_secret_id .'&code=' . $facebook_code;
				$params = null;
				$response = file_get_contents($token_url);
				parse_str($response, $params);
				$graph_url = "https://graph.facebook.com/me?access_token=" . $params['access_token'];
				$user = json_decode(file_get_contents($graph_url));
				$app['session']->set('username', $user->username);
				//echo '<img src="https://graph.facebook.com/' . $user->username. '/picture"/>';
				return $app->redirect($app['url_generator']->generate('_homepage'));
				})
		->value('locale', $fallback_language);


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
				return $app['route_Manager']->get($app,$locale,"home");
				})
		->value('locale', $fallback_language)
			->bind('_homepage');


		$app->get('/{locale}/home', function ($locale) use ($app) {
				return $app['route_Manager']->get($app,$locale,"home");
				})
		->value('locale', $fallback_language)
			->bind('_logout');

		$app->match('/{locale}/admin/check_path', function (Request $request,$locale) use ($app) {
				 $user = $app['session']->get('username');
				return $app['route_Manager']->get($app,$locale,"home");
				})
		->value('locale', $fallback_language)
			->bind('_check_path');


		$app->match('/{locale}/login', function (Request $request, $locale) use ($app) {
				$app['monolog']->addWarning(sprintf(">> Calling Route: _login"));
				$app['translator']->setLocale($locale);
				$app['session']->set('language', $locale);
				$data = array(
					'login' => '',
					'password' => '',
					);

				$form = $app['form.factory']->createBuilder('form', $data)
				->add('login','text',array(
						'label' => $app['translator']->trans('username') . ":"
						)
				     )
				->add('password', 'password', array(
						'label' => $app['translator']->trans('password'). ":",
						'constraints' => array(new Assert\MinLength(5))
						))
				->getForm();


		// display the form
		return new Response($app['twig']->render('login.twig', array(
						'form' => $form->createView(),
						'url' => '/login',
						'locale' => $locale,
						'error' => $app['security.last_error']($request),
						'module' => 'login'))
				, 200, array(
					'Cache-Control' => 's-maxage=5, public',

					));

		})
		->value('locale', $fallback_language)
			->bind('_login');


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
		/*	 $app->get('/{slug}', function ($slug) use ($app) {
			 return $app['route_Manager']->get($app['request'],$slug);
			 })
			 ->assert('slug', '.+');
		 */

		$app->error(function (\Exception $e, $code) use ($app, $fallback_language) {
				$locale = languageUtils::getClientLanguage();
				$app['translator']->setLocale($locale);

				return  new Response($app['twig']->render('error.twig', array(
							'code' => $code,
							'e' => $e,
							'error' => true
							)), 200, array(
							'Cache-Control' => 's-maxage=5, public',
							));

				});

	}



	public function boot() {
	}

	public function get(\Silex\Application $app,$locale,$module = "", $action = "", $id = "") {
		$app['translator']->setLocale($locale);
		$app['session']->set('language', $locale);
		$user = $app['session']->get('username');
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
						'url' => $url,
						'user' => $user
						)), 200, array(
						'Cache-Control' => 's-maxage=3600, public',

						));
	}

	public function post($request ,$module = "", $action = "", $id = "" ) {
	}

}

?>
