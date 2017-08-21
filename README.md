# firebase-rest-php

PHP Wrapper for the 3.x version of Firebase REST API


## Installation

Using Composer

```
$ composer require partner-it/firebase-rest-php
```

## Usage

Create a new client instance:

```PHP
<?php

$fireBaseClient = new FirebaseClient([
	'base_uri' => 'https://xyz.firebaseio.com',
]);
```

Then either generate a token with your secret key:


```PHP
<?php

$fireBaseClient->generateToken('SecretKey', 'useruid');
```

or set a token you already have:


```PHP
<?php

$fireBaseClient->setToken('YourToken');
```

Then use it to make requests:

```PHP
<?php

$response = $fireBaseClient->get('/mypath');

$response = $fireBaseClient->post('/mypath', ['json' => ['key' => 'value']]);
```

The returned object is a `FirebaseResponse` object, to just get the data use

```PHP
<?php

$data = $response->json();
```

To get the status code

```PHP
<?php

$statusCode = $response->getStatusCode();
```

You can also grab the underlying response object with 

```PHP
<?php

$guzzleResponse = $response->getResponse();
```
