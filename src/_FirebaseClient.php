<?php

namespace PartnerIT\Firebase;

use Firebase\Token\TokenException;
use Firebase\Token\TokenGenerator;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;

class FirebaseClient
{

	/**
	 * @var Client
	 */
	private $guzzleClient;

	/**
	 * @var
	 */
	private $token;

	/**
	 * @var HandlerStack
	 */
	private $stack;

	/**
	 * @param array $config
	 */
	public function __construct(array $config = [])
	{
		if (isset($config['base_uri'])) {

			$this->stack = HandlerStack::create();
			$this->guzzleClient = new Client([
				'handler'  => $this->stack,
				'base_uri' => $config['base_uri'],
			]);
		} else {
			throw new \InvalidArgumentException('Config must include a `base_uri`');
		}
	}

	/**
	 * @param Client $guzzelClient
	 */
	public function setGuzzleClient(Client $guzzleClient) {
		$this->guzzleClient = $guzzleClient;
	}

	/**
	 * @param $secret
	 * @param $uid
	 * @param bool $admin
	 * @throws TokenException
	 */
	public function generateToken($secret, $uid, $admin = false)
	{

		$tokenGenerator = new TokenGenerator($secret);
		$tokenGenerator->setData(['uid' => $uid]);

		if ($admin) {
			$tokenGenerator->setOption('admin', true);
		}

		$this->token = $tokenGenerator->create();
	}

	/**
	 * @param $token
	 */
	public function setToken($token)
	{
		$this->token = $token;
	}

	/**
	 * @param $method
	 * @param $args
	 * @return FirebaseResponse
	 */
	public function __call($method, $args)
	{

		if (count($args) < 1) {
			throw new \InvalidArgumentException('Magic request methods require a URI and optional options array');
		}

		$uri = $args[0] . '.json';
		$opts = isset($args[1]) ? $args[1] : [];

		if (isset($opts['query'])) {
			$opts['query'] = $this->getAuthQuery() + $opts['query'];
		} else {
			$opts['query'] = $this->getAuthQuery();
		}

		try {
			$uri = urlencode($uri);
			$response = $this->guzzleClient->request($method, $uri, $opts);

			return new FirebaseResponse($response);
		} catch (ClientException $e) {
			return new FirebaseResponse($e->getResponse());
		}
	}

	/**
	 * @return array
	 */
	public function getAuthQuery()
	{
		if ($this->token) {
			return ['auth' => $this->token];
		}

		return [];
	}

	/**
	 * @param $uri
	 * @return FirebaseResponse
	 */
	public function stream($uri)
	{

		return $this->get($uri, [
			'headers'         => [
				'Accept' => 'text/event-stream'
			],
			'allow_redirects' => true,
			'stream'          => true
		]);
	}

}
