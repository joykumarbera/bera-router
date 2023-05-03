
# Bera Router

A simple php router

## Authors

- [@joykumarbera](https://www.github.com/joykumarbera)

## Features

- Simple Interface
- Support for GET, POST, OPTIONS methods
- Custom 404 page support
- Custom OPTIONS method handling support

## Installation

Install by using composer

```bash
  composer require bera/bera-router
```
    
## Usage/Examples

Quick start

```php
require_once __DIR__  . '/vendor/autoload.php';

$router = new \bera\router\Router();

$router->get('/', function($id) {
    echo 'wellcome to index page';
});

$router->post('/post/create', function(\Symfony\Component\HttpFoundation\Request $request, \Symfony\Component\HttpFoundation\Respone $response) {
    // add new post here
});
```

Using middleware
```php

$router = new \bera\router\Router('\\app\\controllers\\', '\\app\\middlewares\\');


$router->get('/admin/blogs', 'TestController@edit', [
  'before' => ['AuthFilterMiddleware']
]);
```

Using params in route

```php
$router->get('/blog/{id}/edit', 'TestController@edit');
```

Then inside controller we can access the id like this

```php

namespace app\controllers;

class TestController
{
  public function edit($blog_id)
  {
    // edit blog here
  }
}
```

