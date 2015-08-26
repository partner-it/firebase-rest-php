# firebase-rest-php
PHP Wrapper for the Firebase REST API


## Installation

Using Composer

```
composer require partner-it/firebase-rest-php
```

## Usage

Create a new client instance:

```
<?php

$fireBaseClient = new FirebaseClient([
			'base_uri' => 'https://xyz.firebaseio.com',
]);


```

Then either generate a token with your secret key:


```
<?php

$fireBaseClient->generateToken('SecretKey', 'useruid');

```

or set a token you already have:


```
<?php

$fireBaseClient->setToken('YourToken');
```

Then use it to make requests:

```
<?php

$response = $fireBaseClient->get('/mypath');

$response = $fireBaseClient->post('/mypath', ['json' => ['key' => 'value']]);

```

The returned object is a `FirebaseResponse` object, to just get the data use

```
<?php

$data = $response->json();

```

To get the status code

```
<?php

$statusCode = $response->getStatusCode();
```

You can also grab the underlying response object with 

```
<?php

$guzzleResponse = $response->getResponse();

```