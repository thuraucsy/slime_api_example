<?php 
require 'vendor/autoload.php'; // for slim framework
require 'rb.php'; // inlcude pdo orm library
require 'PassHash.php'; // for password hashing functions

// set up database connection
R::setup('mysql:host=localhost;dbname=restapi','root','root');
R::freeze(true);

// initialize app
$app = new \Slim\Slim();

// set default conditions for route param
\Slim\Route::setDefaultConditions(array(
	'id' => '[0-9]{1,}',
));

/*
JSON Sample data for register, login, articles
..............................................
Register
{
 "name": "thura",
 "email": "thura.aung@example.com",
 "password": "thura"
}
Login
{
 "email": "thura.aung@example.com",
 "password": "thura"
}
Articles
{
 "title": "thura Title",
 "url": "thura url",
 "date": "2015-10-17"
}
 */


/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {

  $request = $app->request();

  $body = $request->getBody();
  $input = json_decode($body);


  if (isset($input->name) and isset($input->email) and isset($input->password)) {
    // reading post params
    $name = (string)$input->name;
    $email = (string)$input->email;
    $password_hash = (string)$input->password;

    // validating email address
    validateEmail($email);

    $users = R::dispense('users');
    $users->name = $name;
    $users->email = $email;
    $users->password_hash = PassHash::hash($password_hash);
    $users->api_key = md5(uniqid(rand(), true));
    $id = R::store($users);

    // return JSON-encoded response body
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode(R::exportAll($users)); 

  } else {
    echoRespnse(400, "Please enter valid json with name, email and password");
  }  
});

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {

  $request = $app->request();
  $body = $request->getBody();
  $input = json_decode($body);

  $email = $input->email;
  $password = $input->password;

  // check for correct email and password
  $user = R::findOne('users', 'email=?', array($email));
  $password_hash = $user->password_hash;
  if (PassHash::check_password($password_hash, $password)) {
      if ($user != NULL) {
          $response["error"] = false;
          $response['name'] = $user['name'];
          $response['email'] = $user['email'];
          $response['apiKey'] = $user['api_key'];
          $response['createdAt'] = $user['created_at'];
          //set api key in cookie for future request valid
          $app->setEncryptedCookie('api_key', $user['api_key'], '5 minutes');
      } else {
          // unknown error occurred
          $response['error'] = true;
          $response['message'] = "An error occurred. Please try again";
      }
  } else {
      // user credentials are wrong
      $response['error'] = true;
      $response['message'] = 'Login failed. Incorrect credentials';
  }

  echoRespnse(200, $response);
});

/*
CRUD for articles section
.........................
Create /articles POST
Retrieve /articles GET
Retrieve /articles/:id GET one
Update /articles/:id PUT
Delete /articles/:id Delete
 */
$app->post('/articles', 'authenticate', function () use ($app) {
  try {
	    // get and decode JSON request body
	    $request = $app->request();
	    $body = $request->getBody();
	    $input = json_decode($body);
	    // store article record
	    $article = R::dispense('articles');
	    $article->title = (string)$input->title;
	    $article->url = (string)$input->url;
	    $article->date = (string)$input->date;
	    $id = R::store($article);

	    // return JSON-encoded response body
	    $app->response()->header('Content-Type', 'application/json');
	    echo json_encode(R::exportAll($article));
	} catch (Exception $e) {
	    $app->response()->status(400);
	    $app->response()->header('X-Status-Reason', $e->getMessage());
	}
});

// handle GET requests for /articles
$app->get('/articles', 'authenticate', function () use ($app) {
  try {
    // query database for articles
    $articles = R::find('articles');
    // check request content type
    // format and return response body in specified format
  $mediaType = $app->request()->getMediaType();
  if ($mediaType == 'application/xml') {
    $app->response()->header('Content-Type', 'application/xml');
    $xml = new SimpleXMLElement('<root/>');
    $result = R::exportAll($articles);
  foreach ($result as $r) {
    $item = $xml->addChild('item');
    $item->addChild('id', $r['id']);
    $item->addChild('title', $r['title']);
    $item->addChild('url', $r['url']);
    $item->addChild('date', $r['date']);
  }
  echo $xml->asXml();
  } else if (($mediaType == 'application/json')) {
    $app->response()->header('Content-Type', 'application/json');
    echo json_encode(R::exportAll($articles));
  }
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// handle GET requests for /articles/:id
$app->get('/articles/:id', 'authenticate', function ($id) use ($app) {
  try {
    // query database for single article
    $article = R::findOne('articles', 'id=?', array($id));
    if ($article) {
      // if found, return JSON response
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($article));
    } else {
      $app->response()->status(204);
    }
  } catch (ResourceNotFoundException $e) {
    // return 404 server error
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// handle PUT requests to /articles/:id
$app->put('/articles/:id', 'authenticate', function ($id) use ($app) {
  try {
    // get and decode JSON request body
    $request = $app->request();
    $body = $request->getBody();
    $input = json_decode($body);
    // query database for single article
    $article = R::findOne('articles', 'id=?', array($id));
    // store modified article
    // return JSON-encoded response body
      if ($article) {
      $article->title = (string)$input->title;
      $article->url = (string)$input->url;
      $article->date = (string)$input->date;
      R::store($article);
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($article));
    } else {
      $app->response()->status(204);
    }
  } catch (ResourceNotFoundException $e) {
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// handle DELETE requests to /articles/:id
$app->delete('/articles/:id', 'authenticate', function ($id) use ($app) {
  try {
	    // query database for article
	    $request = $app->request();
	    $article = R::findOne('articles', 'id=?', array($id));
	    // delete article
	    if ($article) {
	      R::trash($article);
	      $app->response()->status(204);
	    } else {
	      throw new ResourceNotFoundException();
	    }
	  } catch (ResourceNotFoundException $e) {
	    $app->response()->status(404);
	  } catch (Exception $e) {
	    $app->response()->status(400);
	    $app->response()->header('X-Status-Reason', $e->getMessage());
	} 
});

/*
Common functions used in above functions
.........................................
authenticate() for authenticate when routing as middleware
validateUserKey() used in authenticate()
validateEmail() for validating email
echoResponse() for echo to client using status code and response content
 */

// route middleware for simple API authentication
function authenticate(\Slim\Route $route) {
  $app = \Slim\Slim::getInstance();
  $api_key = $app->getEncryptedCookie('api_key');
  if (validateUserKey($api_key) === false) {
    $app->halt(401);
  }
}

function validateUserKey($api_key) {
  // check for api key by fetching from db
  $user = R::findOne('users', 'api_key=?', array($api_key));
  if ($user) {
   return true;
  } else {
   return false;
  }
}

/**
 * Validating email address
 */
function validateEmail($email) {
  $app = \Slim\Slim::getInstance();
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $response["error"] = true;
      $response["message"] = 'Email address is not valid';
      echoRespnse(400, $response);
      $app->stop();
  }
}

/**
 * Echoing json response to client
 * @param Int $status_code Http response code
 * @param String $response Json response
 */
function echoRespnse($status_code, $response) {
  $app = \Slim\Slim::getInstance();
  // Http response code
  $app->status($status_code);
  // setting response content type to json
  $app->contentType('application/json');

  echo json_encode($response);
}

// run
$app->run();