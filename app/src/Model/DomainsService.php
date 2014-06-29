<?php

namespace App\Model;

use App\SSH;
use Latte\Engine;
use Nette\Database\Context;


/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class DomainsService extends BaseService
{

	const TABLE_NAME = 'domains';

	private $servers;


	public function __construct(array $servers, Context $context)
	{
		parent::__construct($context);
		$this->servers = $servers;
	}


	public function findOneById($id, $userId)
	{
		$selection = $this->table()->where('id', $id);
		if ($userId !== 1) {
			$selection->where('user_id', $userId);
		}
		return $selection->fetch();
	}


	public function findAll($userId)
	{
		$selection = $this->table()->order('domain ASC');
		if ($userId !== 1) {
			$selection->where('user_id', $userId);
		}
		return $selection;
	}


	public function findAllInPairs($userId)
	{
		return $this->findAll($userId)->fetchPairs('id', 'name');
	}


	public function add($userId, $domain, $target, $cert, $wildcard)
	{
		$this->table()->insert(array(
			'domain' => $domain,
			'target' => $target,
			'certificate_id' => $cert,
			'wildcard' => $wildcard,
			'user_id' => $userId,
		));
	}


	public function save($id, $domain, $target, $cert, $wildcard)
	{
		$this->table()->where('id', $id)->update(array(
			'domain' => $domain,
			'target' => $target,
			'certificate_id' => $cert,
			'wildcard' => $wildcard,
			'need_update' => TRUE,
		));;
	}


	public function remove($id)
	{
		$this->table()->where('id', $id)->update(array(
			'need_remove' => TRUE,
		));;
	}


	public function collectTasks()
	{
		$domains = $this->table()->where('need_update = ? OR need_remove = ?', TRUE, TRUE)->fetchAll();
		$tasks = array();
		foreach ($domains as $domain) {
			if ($domain->need_remove) {
				$tasks[] = function (SSH $ssh) use ($domain) {
					$ssh->rm('/etc/nginx/sites-enabled/100-' . $domain->domain);
				};
				$this->table()->where('id', $domain->id)->delete();
			} else {
				$domains = $domain->domain;
				if ($domain->wildcard) {
					$domains .= ' *.' . $domain->domain;
				}

				$latte = new Engine();
				$data = array(
					'domains' => $domains,
					'target' => $domain->target,
					'slug' => $domain->certificate ? $domain->certificate->slug : 'self',
				);
				$conf = $latte->renderToString(__DIR__ . '/../templates/@nginx.virtulhost.latte', $data);
				file_put_contents(self::getStoragePath() . '/' . $domain->domain, $conf);

				$tasks[] = function (SSH $ssh) use ($domain) {
					$ssh->upload(self::getStoragePath() . '/' . $domain->domain,
						'/etc/nginx/sites-enabled/100-' . $domain->domain);
				};
				$this->table()->where('id', $domain->id)->update(array(
					'need_update' => FALSE,
				));
			}
		}
		return $tasks;
	}


	/************************ helpers ************************/


	public static function getStoragePath()
	{
		return __DIR__ . '/../../data/conf';
	}
}