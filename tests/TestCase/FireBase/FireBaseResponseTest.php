<?php

namespace Mlh\Test\TestCase\FireBase;

use GuzzleHttp\Psr7\Response;
use PartnerIT\Firebase\FireBaseResponse;

class FireBaseResponseTest extends \PHPUnit_Framework_TestCase
{


	public function testParseEventData()
	{

		$response = new \GuzzleHttp\Psr7\Response();
		$fireBaseResponse = new \PartnerIT\Firebase\FireBaseResponse($response);

		$data = <<<'EOD'
event: patch
data: {"path":"/simplelogin:2/path","data":{"modified":"2015-06-30 10:49:20 +0000","title":"Test"}}
EOD;

		$results = $fireBaseResponse->parseEventData($data);

		$exppects = [
			'event' => 'patch',
			'data'  => [
				'path' => '/simplelogin:2/path',
				'data' => [
					'modified' => '2015-06-30 10:49:20 +0000',
					'title'    => 'Test'
				]
			]
		];

		$this->assertEquals($exppects, $results);

	}


	/**
	 *
	 */
	public function testResponseFormatting()
	{

		$guzzleResponse = new Response(200, [],
			'{"path":"/simplelogin:2/path","data":{"modified":"2015-06-30 10:49:20 +0000","title":"Test"}}');
		$response = new FireBaseResponse($guzzleResponse);

		$result = $response->json();

		$exppects = [
			'path' => '/simplelogin:2/path',
			'data' => [
				'modified' => '2015-06-30 10:49:20 +0000',
				'title'    => 'Test'
			]
		];
		$this->assertEquals($exppects, $result);
	}

	/**
	 * Test parsing a response with the stream callback handler
	 */
	public function testParseStream()
	{

		$data = <<<'EOD'
event: patch
data: {"path":"/simplelogin:2/path","data":{"modified":"2015-06-30 10:49:20 +0000","title":"Test"}}
EOD;

		$response = new \GuzzleHttp\Psr7\Response(200, [], $data);
		$fireBaseResponse = new \PartnerIT\Firebase\FireBaseResponse($response);

		$fireBaseResponse->parseStream(function ($data) {
			if (!empty($data)) {
				$exppects = [
					'event' => 'patch',
					'data'  => [
						'path' => '/simplelogin:2/path',
						'data' => [
							'modified' => '2015-06-30 10:49:20 +0000',
							'title'    => 'Test'
						]
					]
				];
				$this->assertEquals($exppects, $data);
			}
		});

	}
}

