<?php

namespace PartnerIT\Firebase;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\HandlerStack;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Rsa\Sha256;

/**
 * Class FirebaseClient
 * @package PartnerIT\Firebase
 * @method FirebaseResponse get() get(string $uri, array $options)
 * @method FirebaseResponse put() put(string $uri, array $options)
 * @method FirebaseResponse post() post(string $uri, array $options)
 * @method FirebaseResponse patch() patch(string $uri, array $options)
 * @method FirebaseResponse delete() delete(string $uri, array $options)
 */
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
     * @var string
     */
    private $privateKey;

    /**
     * @var string
     */
    private $serviceAccount;

    private $apikey;

    /**
     * @var string
     */
    private $idToken;

    /**
     * @var \DateTime
     */
    private $idTokenExpiration;

    /**
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if (!isset($config['privateKey']) || !isset($config['baseUri']) || !isset($config['serviceAccount'])) {
            throw new \InvalidArgumentException('Config must include a `baseUri`, `privateKey` and `serviceAccount`');

        }

        $this->apikey         = $config['apiKey'];
        $this->privateKey     = $config['privateKey'];
        $this->serviceAccount = $config['serviceAccount'];

        $this->stack        = HandlerStack::create();
        $this->guzzleClient = new Client([
            'handler'  => $this->stack,
            'base_uri' => $config['baseUri'],
        ]);
    }

    /**
     * @param Client $guzzelClient
     */
    public function setGuzzleClient(Client $guzzleClient)
    {
        $this->guzzleClient = $guzzleClient;
    }

    /**
     * @param $secret
     * @param $uid
     * @param bool $admin
     * @return $this
     */
    public function generateToken($uid, $admin = false)
    {
        $signer      = new Sha256();
        $token       = (new Builder())
            ->setIssuer($this->serviceAccount)// Configures the issuer (iss claim)
            ->setSubject($this->serviceAccount)// Configures the issuer (sub claim)
            ->setAudience('https://identitytoolkit.googleapis.com/google.identity.identitytoolkit.v1.IdentityToolkit')//(aud claim)
            ->setIssuedAt(time())// Configures the time that the token was issue (iat claim)
            ->setExpiration(time() + 3600)// Configures the expiration time of the token (exp claim)
            ->setId(uniqid(), true)// Configures the id (jti claim), replicating as a header item
            ->set('uid', $uid)// Configures a new claim, called "uid"
            ->set('claims', ['admin' => $admin])
            ->sign($signer, $this->privateKey)
            ->getToken();
        $this->token = (string)$token;

        return $this;
    }

    /**
     * @param $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @return mixed
     */
    public function getToken()
    {
        return $this->token;
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

        $uri  = $args[0] . '.json';
        $opts = isset($args[1]) ? $args[1] : [];

        if (isset($opts['query'])) {
            $opts['query'] = $this->getAuthQuery() + $opts['query'];
        } else {
            $opts['query'] = $this->getAuthQuery();
        }

        try {
            $uri      = urlencode($uri);
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

            if ($this->idToken && $this->idTokenExpiration && $this->idTokenExpiration > new \DateTime('now')) {
                return ['auth' => $this->idToken];
            }

            $resonse = $this->guzzleClient->request('POST',
                'https://www.googleapis.com/identitytoolkit/v3/relyingparty/verifyCustomToken?key=' . $this->apikey, [
                    'json' =>
                        [
                            'returnSecureToken' => true,
                            'token'             => $this->token
                        ]
                ]);

            if ($resonse->getStatusCode() === 200) {
                $return      = json_decode($resonse->getBody()->getContents(), true);
                $safeExpires = $return['expiresIn'] - 120;
                $expires     = new \DateTime('now');
                $expires->add(new \DateInterval('PT' . $safeExpires . 'S'));

                $idToken                 = $return['idToken'];
                $this->idToken           = $idToken;
                $this->idTokenExpiration = $expires;

                return ['auth' => $idToken];
            }
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

    /**
     * Decodes a token.
     *
     * @param string $secret
     *
     * @return object
     */
    public function decodeToken($secret)
    {
        return \JWT::decode($this->token, $secret, ['HS256']);
    }
}
