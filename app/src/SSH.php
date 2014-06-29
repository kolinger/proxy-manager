<?php

namespace App;

use Nette\Object;

/**
 * @author Tomáš Kolinger <tomas@kolinger.name>
 */
class SSH extends Object
{

	/**
	 * @var string
	 */
	private $address;

	/**
	 * @var int
	 */
	private $port;

	/**
	 * @var resource
	 */
	private $connection;

	/**
	 * @var string
	 */
	private $username;

	/**
	 * @var string
	 */
	private $passphrase;

	/**
	 * @var string
	 */
	private $publicCertificate;

	/**
	 * @var string
	 */
	private $privateCertificate;

	/**
	 * @var string
	 */
	private $password;


	/**
	 * @param string $address
	 * @param int $port
	 * @throws \Nette\InvalidStateException
	 */
	public function __construct($address, $port = 22)
	{
		if (!function_exists('ssh2_connect')) {
			throw new \Nette\InvalidStateException('SSH class need ssh2 extension');
		}

		$this->address = $address;
		$this->port = $port;
	}


	/**
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}


	/**
	 * @param string $public
	 * @param string $private
	 * @param string $passphrase
	 */
	public function setCertificates($public, $private, $passphrase = NULL)
	{
		$this->publicCertificate = $public;
		$this->privateCertificate = $private;
		$this->passphrase = $passphrase;
	}


	/**
	 * @param string $password
	 */
	public function setPassword($password)
	{
		$this->password = $password;
	}


	/**
	 * @param string $command
	 * @return string
	 */
	public function execute($command)
	{
		$this->connect();

		$stream = ssh2_exec($this->connection, $command);
		stream_set_blocking($stream, TRUE);
		return stream_get_contents($stream);
	}


	/**
	 * @param string $remoteFile
	 * @param string $localFile
	 */
	public function download($remoteFile, $localFile)
	{
		$this->connect();

		ssh2_scp_recv($this->connection, $remoteFile, $localFile);
	}


	/**
	 * @param string $localFile
	 * @param string $remoteFile
	 * @param int $mode
	 */
	public function upload($localFile, $remoteFile, $mode = NULL)
	{
		$this->connect();

		$sftp = ssh2_sftp($this->connection);
		$sftpStream = fopen('ssh2.sftp://' . $sftp . $remoteFile, 'w');
		try {
			if (!$sftpStream) {
				throw new \Exception('Could not open remote file: ' . $remoteFile);
			}

			$data = file_get_contents($localFile);

			if ($data === FALSE) {
				throw new \Exception('Could not open local file: ' . $localFile);
			}

			if (fwrite($sftpStream, $data) === FALSE) {
				throw new \Exception('Could not send data from file: ' . $localFile);
			}

			fclose($sftpStream);
		} catch (\Exception $e) {
			fclose($sftpStream);
			throw new $e;
		}
	}


	/**
	 * @param string $file
	 */
	public function rm($file)
	{
		$this->connect();

		$this->execute('rm ' . $file);
	}


	private function connect()
	{
		if ($this->connection === NULL) {
			$this->connection = ssh2_connect($this->address, $this->port);
			$this->authenticate();
		}
	}


	private function authenticate()
	{
		if ($this->username === NULL) {
			throw new \Nette\InvalidStateException('Missing username, use SSH::setUsername()');
		}

		if ($this->publicCertificate !== NULL && $this->privateCertificate !== NULL) {
			ssh2_auth_pubkey_file($this->connection, $this->username, $this->publicCertificate, $this->privateCertificate, $this->passphrase);
		} else {
			if ($this->password !== NULL) {
				ssh2_auth_password($this->connection, $this->username, $this->password);
			} else {
				throw new \Nette\InvalidStateException('Use password based authentication (SSH::setPassword()) or certificate based authentication (SSH::setCertificates())');
			}
		}
	}


	public function close()
	{
		$this->execute('exit;');
		$this->connection = NULL;
	}
}