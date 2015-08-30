<?php 
require 'vendor/autoload.php';
require 'rb.php';

// $app = new \Slim\Slim();
// $app->get('/hello/:name', function($name) 
// {
// 	echo "Hello, " . $name;
// });
// $app->run();
 
// register Slim auto-loader
\Slim\Slim::registerAutoloader();

// set up database connection
R::setup('mysql:host=localhost;dbname=restapi','root','root');
R::freeze(true);

// initialize app
$app = new \Slim\Slim();
// handle GET requests for /articles

// set default conditions for route param
\Slim\Route::setDefaultConditions(array(
	'id' => '[0-9]{1,}',
));

// route middleware for simple API authentication
function authenticate(\Slim\Route $route) {
   	$app = \Slim\Slim::getInstance();
    $uid = $app->getEncryptedCookie('uid');
    $key = $app->getEncryptedCookie('key');
    if (validateUserKey($uid, $key) === false) {
      $app->halt(401);
    }
}

function validateUserKey($uid, $key) {
	// insert your (hopefully more complex) validation routine here
	if ($uid == 'demo' && $key == 'demo') {
	return true;
	} else {
	return false;
	}
}


// handle GET requests for /articles
$app->get('/articles', function () use ($app) {
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

// generates a temporary API key using cookies
// call this first to gain access to protected API methods
$app->get('/demo', function () use ($app) {
  try {
    $app->setEncryptedCookie('uid', 'demo', '5 minutes');
    $app->setEncryptedCookie('key', 'demo', '5 minutes');
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
} });

// handle GET requests for /articles/:id
$app->get('/articles/:id', function ($id) use ($app) {
  try {
    // query database for single article
    $article = R::findOne('articles', 'id=?', array($id));
    if ($article) {
      // if found, return JSON response
      $app->response()->header('Content-Type', 'application/json');
      echo json_encode(R::exportAll($article));
    } else {
      // else throw exception
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    // return 404 server error
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// handle POST requests to /articles

// $app->post('articles', function () use ($app) {
//   try {
//     // check request content type
//     // decode request body in JSON or XML format
//     $request = $app->request();
//     $mediaType = $request->getMediaType();
//     $body = $request->getBody();
//     if ($mediaType == 'application/xml') {
//       $input = simplexml_load_string($body);
//     } elseif ($mediaType == 'application/json') {
//       $input = json_decode($body);
//     }
//     // create and store article record
//     $article = R::dispense('articles');
//     $article->title = (string)$input->title;
//     $article->url = (string)$input->url;
//     $article->date = (string)$input->date;
//     $id = R::store($article);
//     // return JSON/XML response
//     if ($mediaType == 'application/xml') {
//       $app->response()->header('Content-Type', 'application/xml');
//       $xml = new SimpleXMLElement('<root/>');
//       $result = R::exportAll($article);
//       foreach ($result as $r) {
//         $item = $xml->addChild('item');
//         $item->addChild('id', $r['id']);
//         $item->addChild('title', $r['title']);
//         $item->addChild('url', $r['url']);
//         $item->addChild('date', $r['date']);
// }
//       echo $xml->asXml();
//     } elseif ($mediaType == 'application/json') {
//       $app->response()->header('Content-Type', 'application/json');
//       echo json_encode(R::exportAll($article));
//     }
//   } catch (Exception $e) {
//     $app->response()->status(400);
//     $app->response()->header('X-Status-Reason', $e->getMessage());
// } });
// 
$app->post('/articles', function () use ($app) {
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

// handle PUT requests to /articles/:id
$app->put('/articles/:id', function ($id) use ($app) {
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
      throw new ResourceNotFoundException();
    }
  } catch (ResourceNotFoundException $e) {
    $app->response()->status(404);
  } catch (Exception $e) {
    $app->response()->status(400);
    $app->response()->header('X-Status-Reason', $e->getMessage());
  }
});

// handle DELETE requests to /articles/:id
$app->delete('/articles/:id', function ($id) use ($app) {
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

// run
$app->run();