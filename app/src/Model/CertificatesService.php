<?php

namespace App\Model;

use App\SSH;
use Nette\Http\FileUpload;
use Nette\Utils\Strings;


/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class CertificatesService extends BaseService
{

	const TABLE_NAME = 'certificates';


	public function findOneById($id)
	{
		return $this->table()->where('id', $id)->fetch();
	}


	public function findAll($userId)
	{
		$selection = $this->table()->order('name ASC');
		if ($userId !== 1) {
			$selection->where('user_id', $userId);
		}
		return $selection;
	}


	public function findAllInPairs($userId)
	{
		return $this->findAll($userId)->fetchPairs('id', 'name');
	}


	public function save($userId, $name, FileUpload $key, FileUpload $crt, FileUpload $ca)
	{
		$pem = file_get_contents($crt);
		if ($ca->error !== UPLOAD_ERR_NO_FILE) {
			$pem .= "\n" . file_get_contents($ca);
		}

		$key = file_get_contents($key);

		$slug = Strings::toAscii($name);
		$slug = strtolower($slug);
		$slug = preg_replace('#[^a-z0-9]+#i', '', $slug);
		$slug = trim($slug);

		file_put_contents(self::getStoragePath() . '/' . $slug . '.key', $key);
		file_put_contents(self::getStoragePath() . '/' . $slug . '.pem', $pem);

		$this->table()->insert(array(
			'user_id' => $userId,
			'name' => $name,
			'slug' => $slug,
		));
	}


	public function remove($id)
	{
		$certificate = $this->findOneById($id);
		@unlink(self::getStoragePath() . '/' . $certificate->slug . '.key');
		@unlink(self::getStoragePath() . '/' . $certificate->slug . '.pem');
		$this->table()->where('id', $id)->update(array(
			'need_remove' => TRUE,
		));
	}


	public function collectTasks()
	{
		$certificates = $this->table()->where('need_update = 1 OR need_remove = 1');
		$tasks = array();
		foreach ($certificates as $certificate) {
			if ($certificate->need_remove) {
				$tasks[] = function (SSH $ssh) use ($certificate) {
					$ssh->rm('/etc/ssl/my/' . $certificate->slug . '.key');
					$ssh->rm('/etc/ssl/my/' . $certificate->slug . '.pem');
				};
				$this->table()->where('id', $certificate->id)->delete();
			} else {
				$tasks[] = function (SSH $ssh) use ($certificate) {
					$ssh->rm('/etc/ssl/my/' . $certificate->slug . '.key');
					$ssh->rm('/etc/ssl/my/' . $certificate->slug . '.pem');

					$ssh->upload(self::getStoragePath() . '/' . $certificate->slug . '.key',
						'/etc/ssl/my/' . $certificate->slug . '.key');
					$ssh->upload(self::getStoragePath() . '/' . $certificate->slug . '.pem',
						'/etc/ssl/my/' . $certificate->slug . '.pem');
				};
				$this->table()->where('id', $certificate->id)->update(array(
					'need_update' => FALSE,
				));
			}
		}
		return $tasks;
	}


	/************************ helpers ************************/


	public static function getStoragePath()
	{
		return __DIR__ . '/../../data/certificates';
	}
}