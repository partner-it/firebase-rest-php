<?php

namespace PartnerIT\Test\TestCase\FireBase;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PartnerIT\Firebase\FireBaseClient;
use PartnerIT\Firebase\FireBaseResponse;

class FireBaseClientTest extends \PHPUnit_Framework_TestCase
{

	/**
	 * @var FireBaseClient
	 */
	protected $client;

	public function setUp()
	{
		parent::setUp();
		$client = new FireBaseClient(['base_uri' => 'https://example.com']);
		$this->client = $client;
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Config must include a `base_uri`
	 */
	public function testInitError()
	{
		$client = new FireBaseClient();
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Magic request methods require a URI and optional options array
	 */
	public function testMagic()
	{
		$this->client->get();
	}

	public function testCalls()
	{

		$mock = new MockHandler([
			new Response(200, [],
				'{"path":"/simplelogin:2/path","data":{"modified":"2015-06-30 10:49:20 +0000","title":"Test"}}'),
			new Response(401, [], json_encode([
				'error' => 'Permission denied'
			]))

		]);

		$handler = HandlerStack::create($mock);
		$guzzleClient = new Client(['handler' => $handler]);

		$this->client->setGuzzleClient($guzzleClient);

		$response = $this->client->get('/someurl');
		$this->assertInstanceOf('\PartnerIT\Firebase\FireBaseResponse', $response);

		/**
		 * @var $response FireBaseResponse
		 */
		$response = $this->client->get('/someurl/noaccess');
		$this->assertInstanceOf('\PartnerIT\Firebase\FireBaseResponse', $response);

		$this->assertEquals(['error' => 'Permission denied'], $response->json());
	}

	/**
	 * Test that we can decode our generated token
	 */
	public function decodeToken() {
		$this->client->generateToken('mysecret', 'userid', true);
		$tokenDecoded =$this->client->decodeToken('mysecret');
		$this->assertEquals('userid', $tokenDecoded->d->uid);
	}

}
