<?php 
namespace api\Security\Authentication\Provider;

use Symfony\Component\Security\Core\Authentication\Provider\AuthenticationProviderInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\NonceExpiredException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use api\Security\Authentication\Token\CluddyUserToken;

class CluddySecurityProvider implements AuthenticationProviderInterface
{
	private $userProvider;
	private $cacheDir;

	public function __construct(UserProviderInterface $userProvider, $cacheDir, \api\Logger\CluddyLogger $logger)
	{
		$this->userProvider = $userProvider;
		$this->cacheDir     = $cacheDir;
		$this->logger     = $logger;
	}

	public function authenticate(TokenInterface $token)
	{
		if($token->source == "facebook" || $token->source == "twitter" )
		{
			$this->logger->info($token->source . " Authentication Asked");
			$user = $this->userProvider->loadUserByUsername($token->getUsername());
			if(!$user) {
				$this->logger->info("User Not exist");
				$user = $this->userProvider->CreateUser($token);
			}
			if($user) {
				$this->logger->info("User already exist");
				$this->logger->info("Authentication passed");

				$authenticatedToken = new CluddyUserToken($user->getRoles());
				$authenticatedToken->setUser($user);
				return $authenticatedToken;
			}
		} else {
			$user = $this->userProvider->loadUserByUsername($token->getUsername());
			if ($user && $this->validateHash($user->getPassword(), $token->password)) {
				$this->logger->info("Authentication passed");
				$authenticatedToken = new CluddyUserToken($user->getRoles());
				$authenticatedToken->setUser($user);
				return $authenticatedToken;
			}
		}
		throw new AuthenticationException('The Cluddy authentication failed.');
	}

	public function validateHash($hash, $password) {
		return ($hash == crypt($password, $hash));
	}

	public function supports(TokenInterface $token)
	{
		return $token instanceof CluddyUserToken;
	}
}
