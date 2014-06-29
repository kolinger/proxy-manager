<?php

namespace App\Model;


/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class MiscService extends BaseService
{

	const TABLE_NAME = 'misc';


	public function getLastUpdateDate()
	{
		return $this->table()
			->where('key', 'LAST_UPDATE')
			->fetch()->value;
	}


	public function setLastUpdate()
	{
		$this->table()->where('key', 'LAST_UPDATE')->update(array(
			'value' => time(),
		));
	}
}