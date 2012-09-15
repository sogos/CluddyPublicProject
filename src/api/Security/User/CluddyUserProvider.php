<?php

namespace api\Security\User;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Table;

class CluddyUserProvider implements UserProviderInterface
{
	private $conn;

	public function __construct(Connection $conn, \api\Logger\CluddyLogger $logger)
	{
		$this->conn = $conn;
		$this->logger = $logger;
		$this->checkDatabase();
	}


	public function loadUserByUsername($username)
	{
		$stmt = $this->conn->executeQuery('SELECT * FROM users WHERE username = ?', array(strtolower($username)));
		if (!$user = $stmt->fetch()) {
			return false;
//			throw new UsernameNotFoundException(sprintf('Username "%s" does not exist.', $username));
		}
		$this->logger->info(sprintf('Username "%s" does exist.', $username));
		return new User($user['username'],  $user['hash'], explode(',', $user['roles']), true, true, true, true);
	}

	public function refreshUser(UserInterface $user)
	{
		if (!$user instanceof User) {
			throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
		}

		return $this->loadUserByUsername($user->getUsername());
	}

	public function supportsClass($class)
	{
		return $class === 'Symfony\Component\Security\Core\User\User';
	}

	public function createUser(\api\Security\Authentication\Token\CluddyUserToken $token) {
		if($token->source == "twitter") {
			$salt = substr(str_replace('+', '.', base64_encode(sha1(microtime(true), true))), 0, 22);
			$hash = crypt($token->password, '$2a$12$' . $salt);

			$schema = $this->conn->getSchemaManager();
			$this->conn->executeQuery('INSERT INTO users (username, hash, twitter_access_token, twitter_secret_token, twitter_user_id, roles) VALUES ("'. $token->getUserName().'", "' . $hash . '",  "'.$token->access_token .'","'.$token->access_token_secret .'" , "' . $token->twitter_user_id . '",  "ROLE_ADMIN")');
		} else {
			$salt = substr(str_replace('+', '.', base64_encode(sha1(microtime(true), true))), 0, 22);
			$hash = crypt($token->password, '$2a$12$' . $salt);

			$schema = $this->conn->getSchemaManager();
			$this->conn->executeQuery('INSERT INTO users (username, hash, roles) VALUES ("'. $token->getUserName().'", "'.$hash.'", "ROLE_ADMIN")');

		}
		return $this->loadUserByUsername($token->getUserName());
	}

	public function checkDatabase() {
		$schema = $this->conn->getSchemaManager();
		if (!$schema->tablesExist('users')) {
			$users = new Table('users');
			$users->addColumn('id', 'integer', array('unsigned' => true, 'autoincrement' => true));
			$users->setPrimaryKey(array('id'));
			$users->addColumn('username', 'string', array('length' => 32));
			$users->addUniqueIndex(array('username'));
			$users->addColumn('hash', 'string', array('length' => 255));
			$users->addColumn('twitter_access_token', 'string', array('length' => 255));
			$users->addColumn('twitter_secret_token', 'string', array('length' => 255));
			$users->addColumn('twitter_user_id', 'string', array('length' => 255));
			$users->addColumn('email', 'string', array('length' => 255));
			$users->addColumn('roles', 'string', array('length' => 255));
			$schema->createTable($users);
			$this->conn->executeQuery('INSERT INTO users (username, hash, roles) VALUES ("admin", "$2a$12$rsAATFrZ8qBrBhBYjn6gGO7N5YYSYHZfx52AzePaNSG.UTJ.qH6pa", "ROLE_ADMIN")');

		}
	}

}
