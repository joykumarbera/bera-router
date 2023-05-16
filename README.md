
# Bera Router

A simple php router

## Authors

- [@joykumarbera](https://www.github.com/joykumarbera)

## Features

- Simple Interface
- Support for GET, POST, OPTIONS methods
- Custom 404 page support

## Installation

Install by using composer

```bash
  composer require bera/bera-router
```
    
## Usage

### Quick start

Default controller and middleware namespace is set to ```\app\controllers``` and ```\app\middlewares``` which can be set when instantiating the main router object

```php
require_once __DIR__  . '/vendor/autoload.php';

$router = new \bera\router\Router();

$router->get('/', function($id) {
    echo 'welcome to index page';
});

$router->post('/post/create', function(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Respone $response) {
    // add new post here
});

$router->dispatch();
```

### Using middleware

Setup up the controller and middleware namespace

```php

$router = new \bera\router\Router('\\app\\controllers\\', '\\app\\middlewares\\');


$router->get('/admin/blogs', 'TestController@edit', [
  'before' => ['AuthFilterMiddleware']
]);
```

### Using params in route

```php
$router->get('/blog/{id}/edit', 'BlogController@edit');
```

Then inside controller we can access the id like this

```php

namespace app\controllers;

class BlogController
{
  public function edit($blog_id)
  {
    // edit blog here
  }
}
```

### Setup 404 page route handler

```php
$router = new \bera\router\Router();
$router->set404Route('SomeController@handle404');
```