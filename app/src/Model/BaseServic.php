<?php

namespace App\Model;

use Nette\Database\Context;
use Nette\Object;


/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
abstract class BaseService extends Object
{

	const TABLE_NAME = 'unknown';

	/** @var Context */
	protected $context;


	public function __construct(Context $context)
	{
		$this->context = $context;
	}

	protected function table()
	{
		return $this->context->table(static::TABLE_NAME);
	}
}