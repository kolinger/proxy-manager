<?php

namespace App\Model;

use Nette\Database\Context;
use Nette\Security\AuthenticationException;
use Nette\Security\IAuthenticator;
use Nette\Security\Identity;


/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class UsersService extends BaseService implements IAuthenticator
{

	const TABLE_NAME = 'users';

	/** @var PasswordEncoder */
	private $passwordEncoder;


	public function __construct(Context $context, PasswordEncoder $passwordEncoder)
	{
		parent::__construct($context);
		$this->passwordEncoder = $passwordEncoder;
	}


	public function findOneById($id)
	{
		return $this->table()->where('id', $id)->fetch();
	}


	public function findOneByLogin($login)
	{
		return $this->table()->where('login', $login)->fetch();
	}


	public function authenticate(array $credentials)
	{
		list($login, $password) = $credentials;

		$user = $this->findOneByLogin($login);
		if (!$user) {
			throw new AuthenticationException('The username is incorrect.', self::IDENTITY_NOT_FOUND);
		} elseif (!$this->passwordEncoder->matches($password, $user->password)) {
			throw new AuthenticationException('The password is incorrect.', self::INVALID_CREDENTIAL);
		}

		return new Identity($user->id, array('authenticated'), array('login' => $user->login));
	}
}