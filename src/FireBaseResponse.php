<?php

namespace PartnerIT\FireBase;

use Psr\Http\Message\ResponseInterface;

/**
 * Class FireBaseResponse
 */
class FireBaseResponse
{

	/**
	 * @var ResponseInterface
	 */
	private $response;

	/**
	 * @param ResponseInterface $response
	 */
	public function __construct(ResponseInterface $response)
	{

		$this->response = $response;
	}

	/**
	 * @return string
	 */
	public function json()
	{
		$this->response->getBody()->rewind();
		return json_decode($this->response->getBody()->getContents(), true);
	}

	/**
	 * @param callable $fn
	 */
	public function parseStream(callable $fn)
	{
		$body = $this->response->getBody();
		while (!$body->eof()) {
			$data = $this->parseEventData($body->read(1024));
			$fn($data);
		}
	}

	/**
	 * Parse EventSource Data from a stream
	 *
	 * @param $data
	 */
	public function parseEventData($data)
	{

		$out = explode("\n", $data);

		$output = [];

		foreach ($out as $line) {
			$matches = [];
			preg_match('/^([a-z]+): (.*)/', $line, $matches);
			if (count($matches) === 3) {

				list($all, $type, $payload) = $matches;

				switch ($type) {
					case 'data':
						$output['data'] = json_decode($payload, true);
						break;

					case 'event':
						$output['event'] = $payload;
						break;
				}
			}
		}

		return $output;
	}

	/**
	 * @return ResponseInterface
	 */
	public function getResponse()
	{
		return $this->response;
	}

	/**
	 * @return int
	 */
	public function getStatusCode() {
		return $this->response->getStatusCode();
	}

}
