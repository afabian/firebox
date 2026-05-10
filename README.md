# Firebox

A lightweight PHP web application framework. Firebox handles routing, compilation, and the MVC request lifecycle so you can focus on writing controller, model, and view code.

## Requirements

- PHP 7+ with `mysqli` extension
- A web server (Apache, Nginx, etc.) that rewrites all requests to `index.php`, or direct access to `index.php` via query string
- MySQL (optional — only required if your application uses a database)

## Installation

1. Place the `firebox/` directory somewhere accessible to your web server (it does not need to be inside the document root).
2. Create a project directory (your document root) and add `index.php`:

```php
<?php
require('/path/to/firebox/firebox_runtime.php');
```

3. Create a `settings.php` in the same directory:

```php
<?php
$fbx['settings'] = array(
    'name'               => 'My App',
    'password'           => 'changeme',
    'default_action'     => 'mycontroller.show_home',
    'development_plugins' => array('debug'),
    'production_plugins'  => array(),
    'pre'                => array(),
    'post'               => array(),
    'mysqli_host'        => '',
    'mysqli_database'    => '',
    'mysqli_user'        => '',
    'mysqli_pass'        => '',
);
```

4. Create the required directories:

```
parsed/
parsed/dev/
parsed/prod/
controller/
model/
view/
layout/
plugins/
lib/
```

5. Write your first controller in `controller/mycontroller.php` and navigate to `index.php?go=mycontroller.show_home`.

## Demo App

A complete To-Do List demo is included in `skeleton/`. Copy the skeleton files into a new project directory to see a working example.

## URL Parameters (Development Mode Only)

| Parameter | Effect |
|---|---|
| `?fbx_pass=PASSWORD` | Unlock development features in production |
| `?fbx_develop` | Force development mode |
| `?fbx_debug_to_screen` | Stream debug output directly to the page |
| `?fbx_skip_tests` | Skip automatic unit test execution |
| `?fbx_compiler_debug` | Show compiler internals in debug pane |

## Production Mode

Create an empty file at `parsed/production` to activate production mode. This disables debug output, skips compilation checks (uses cached files), and loads `production_plugins` instead of `development_plugins`. Remove the file to return to development mode, or use `?go=fbx.stop_production`.
