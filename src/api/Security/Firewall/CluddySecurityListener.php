<?php
namespace api\Security\Firewall;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\Security\Http\Firewall\ListenerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\SecurityContextInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use api\Security\Authentication\Token\CluddyUserToken;
use Symfony\Component\Validator\Constraints as Assert;

class CluddySecurityListener implements ListenerInterface
{
	protected $securityContext;
	protected $authenticationManager;

	public function __construct(SecurityContextInterface $securityContext, AuthenticationManagerInterface $authenticationManager, \Silex\Application $app, $options)
	{
		$this->securityContext = $securityContext;
		$this->authenticationManager = $authenticationManager;
		$this->app = $app;
	}

	protected function requiresAuthentication(Request $request)
	{
		return $this->httpUtils->checkRequestPath($request, $this->options['check_path']);
	}




	public function handle(GetResponseEvent $event)
	{
			$request = $event->getRequest();
		if ('POST' == $request->getMethod()) {
			$data = array(
					'login' => '',
					'password' => '',
				     );

			$form = $this->app['form.factory']->createBuilder('form', $data)
				->add('login','text',array(
							'constraints' => array(new Assert\NotBlank())

							)
				     )
				->add('password', 'password', array(
							'required' => true,
							'constraints' => array(new Assert\MinLength(5),
								new Assert\NotBlank())
							))
				->getForm();

			$form->bindRequest($request);
			if ($form->isValid()) {
				$data = $form->getData();
				$token = new CluddyUserToken();
				$token->setUser($data['login']);
				$token->password = $data['password'];
				try {
					$returnValue = $this->authenticationManager->authenticate($token);
					if ($returnValue instanceof TokenInterface) {
						return $this->securityContext->setToken($returnValue);
					} elseif ($returnValue instanceof Response) {
						return $event->setResponse($returnValue);
					}
				} catch (AuthenticationException $e) {
				}
			}
		} elseif('GET' == $request->getMethod()) {
			$this->app['logger']->warn('Authenticating with Get Method');
			$source = $request->query->get('source');
			$username = $request->query->get('username');
			if(!empty($source)) {
				if($source == "facebook") {
					$this->app['logger']->warn('Facebook entry');
					$email = $request->query->get('email');
				}
				if($source == "twitter") {
					$this->app['logger']->warn('Twitter entry');
					$access_token = $request->query->get('access_token');
					$access_token_secret = $request->query->get('access_token_secret');
					$twitter_user_id = $request->query->get('twitter_user_id');
				}
				$token = new CluddyUserToken();
				$token->setUser($username);
				$token->password = uniqid();
				$token->source = $source;
				if($source == "facebook") {
					$token->email = $email;
				} elseif($source == "twitter") {
					$token->access_token = $access_token;
					$token->access_token_secret = $access_token_secret;
					$token->twitter_user_id = $twitter_user_id;
				}
				try {
					$returnValue = $this->authenticationManager->authenticate($token);
					if ($returnValue instanceof TokenInterface) {
						return $this->securityContext->setToken($returnValue);
					} elseif ($returnValue instanceof Response) {
						return $event->setResponse($returnValue);
					}
				} catch (AuthenticationException $e) {
				}
			}
		}

	} 


}

