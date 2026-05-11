# Firebox Developer Documentation

## Architecture Overview

Firebox is a PHP MVC framework with a custom compilation step. The compilation step renames functions to avoid global namespace collisions, injects global variable declarations, and inlines `pre()`/`post()` hook code. All source files are compiled to `parsed/dev/` or `parsed/prod/` and cached by mtime.

### Block Diagram

```
Browser Request
      |
   index.php
      |
   firebox_runtime.php
      |-- loads settings.php
      |-- loads plugins
      |-- runs prerequest plugins
      |-- resolves action from ?go=controller.method
      |-- runs preexec plugins
      |-- runs global pre-actions (settings['pre'])
      |
      |-- fbx.* actions  -->  admin/*.php (framework admin)
      |-- plugin.* actions --> plugin function directly
      |-- user actions    --> control()
                                  |
                            firebox_compiler.php  (if cache stale)
                                  |
                            parsed/dev/controller.name.php
                                  |
                            controller_name_method()
                                  |-- pre() contents inlined at top
                                  |-- method body
                                  |-- post() contents inlined at bottom (returns layout)
                                  |
                              query() / action() / display() / layout()
                                  |
                            fbx_run_file()  -->  compiled model/view/layout file
      |
      |-- runs posthtml plugins (debug output injected here)
      |-- echoes $content['html']
      |-- runs global post-actions
      |-- runs postexec plugins
```

---

## Directory Structure (Project)

```
index.php                  Entry point — just requires firebox_runtime.php
settings.php               Site configuration
parsed/
  dev/                     Compiled files (development mode)
  prod/                    Compiled files (production mode)
  production               Empty file — presence activates production mode
controller/
  foo.php                  Controller: defines methods, pre(), post()
model/
  foo/
    qry_something.php      Query: data retrieval/mutation
    do_something.php       Action: business logic
view/
  foo/
    my_view.php            View: HTML output
layout/
  foo/
    my_layout.php          Layout: HTML wrapper (called from post())
plugins/
  debug.php                Debug pane plugin (included in skeleton)
  profiler.php             Timing profiler plugin
  queries.php              SQL query logger plugin
  menu.php                 Firebox admin menu plugin
lib/
  utility.php              Shared utility functions (auto-discovered)
```

---

## MVC Layers

### Controllers (`controller/name.php`)

- Each file is one controller. Functions become callable methods.
- Called via `control('name.method')` or `control('method')` (relative, within same controller).
- Three special functions:
  - `pre()` — body is injected at the top of every method in the file
  - `post()` — body is injected at the bottom of every method; typically calls `layout()` and returns it
  - Regular methods — the action body
- Compiled function name: `controller_name_method`
- Test function name: `test_name_method` (defined in the controller file itself)
- **Naming constraint**: do not name controller methods starting with `test_` — the compiler treats those as test functions, not callable methods.

Example:
```php
function pre() {
    $db = action('connect_db');
}

function show_list() {
    $items = query('get_items');
    $content['body'] = display('item_list', $items);
}

function post() {
    return layout('main_layout');
}
```

### Models (`model/controllername/filename.php`)

- Called via `query('filename')` or `action('filename')` or `action_once('filename')`.
- The filename (without `.php`) must match the main function name in the file.
- Functions other than the main function and its test are not renamed — they stay in the global namespace.
- `action_once()` — runs the file at most once per request (tracked in `$fbx['files_run']`).
- Compiled function name: `model_controllername_filename_filename`
- Test function name: `test_model_controllername_filename_filename`

### Views (`view/controllername/filename.php`)

- Called via `display('filename')`.
- The file can either echo output directly, or define a function matching the filename that accepts parameters.
- If a function is defined and it returns a value, that's used. If it only echoes, the output buffer is captured.
- Compiled function name: `view_controllername_filename_filename`

### Layouts (`layout/controllername/filename.php`)

- Called via `layout('filename')`, typically from `post()`.
- Receives rendered page content in `$content['body']`.
- Runs `fbx_execute_plugins('prehtml')` — this is where debug panes are injected.
- Same function/return rules as views.

### Libraries (`lib/*.php`)

- Auto-discovered on first compile. Functions defined in lib files are available to all controllers/models/views.
- `firebox_compiler.php` scans the lib directory, maps function names to source files, and inserts `require_once()` calls into any compiled file that references them.
- Compiled (indexed) to `parsed/dev/lib.filename.php` — contains a PHP array of `function_name => source_file`.

---

## Compilation System

`firebox_compiler.php` contains a hand-written lexical parser and code transformer.

### Pipeline (per file)

1. **Lex** (`fbx_lexical_parser`): Tokenizes the source into lexemes using a state machine. States: `html`, `php`, `singlequote`, `doublequote`, `comment`, `linecomment`, `heredoc`, etc. Rules defined in `firebox_compiler_data.php`.

2. **Block detection** (`fbx_get_blocks_from_lex`): Identifies all `{...}` blocks and what keyword precedes them (`function`, `if`, `while`, etc.). For `function` blocks, captures the function name. Tracks nesting depth.

3. **Function reference detection** (`fbx_get_function_references_from_lex`): Identifies all function call sites (words that aren't language keywords, aren't variable names, and aren't function definitions).

4. **Pre/post extraction**: Finds `pre()` and `post()` function bodies to be inlined into other functions.

5. **Function renaming**: For controllers, all non-pre/post functions are renamed to `controller_filename_method`. For model/view files, only the function matching the filename (and its `test_` variant) is renamed.

6. **Library injection**: For each renamed function, scans its function references and inserts `require_once()` for any lib functions used.

7. **Pre/post inlining**: The opening `{` of each renamed function is replaced with `{ global $fbx, $content; [lib requires] [pre body]`. The closing `}` is replaced with `[post body] }}` (double-close to close both the `if (!function_exists(...))` wrapper and the function itself).

8. **Output**: Modified lexemes are reassembled into a string (skipping the original `pre`/`post` function blocks) and written to `parsed/dev/` or `parsed/prod/`.

### Compiled Output Example

Source:
```php
function pre() { $x = 1; }
function show() { echo $x; }
function post() { return layout('main'); }
```

Compiled `controller.foo.php`:
```php
if (!function_exists('controller_foo_show')) { function controller_foo_show() {
    global $fbx, $content;
    $x = 1;               // pre() body inlined
    echo $x;
    return layout('main'); // post() body inlined
}}
```

### Caching

- Compiled files are cached by mtime: if `parsed/dev/file.php` mtime >= source mtime, compilation is skipped. After writing a compiled file, its mtime is explicitly set to match the source via `touch()`, so equal timestamps mean "compiled from this exact version."
- In dev mode the compiler is called on every request but exits immediately if the cache is current.
- In production mode, compilation is never triggered — only the cached `parsed/prod/` files are used. To recompile, run `admin.start_production`, which first clears all files from `parsed/prod/` and then recompiles everything. This ensures removed or renamed source files don't leave stale compiled output behind.
- `$fbx['compiled']` tracks files already compiled this request to avoid double-compilation within a single request.

---

## Plugin System

Plugins register callbacks for lifecycle phases. Any plugin file can call `fbx_plugin_register($phase, $callback)`.

### Phases (in order)

| Phase | Triggered by |
|---|---|
| `prerequest` | Runtime, before action resolution |
| `preexec` | Runtime, after action resolved, before execution |
| `precontrol` | `control()`, before running a controller method |
| `postcontrol` | `control()`, after running a controller method |
| `prequery` | `fbx_run_file()`, before running a query |
| `postquery` | `fbx_run_file()`, after running a query |
| `preaction` | `fbx_run_file()`, before running an action |
| `postaction` | `fbx_run_file()`, after running an action |
| `predisplay` | `fbx_run_file()`, before running a view |
| `postdisplay` | `fbx_run_file()`, after running a view |
| `prelayout` | `fbx_run_file()`, before running a layout |
| `postlayout` | `fbx_run_file()`, after running a layout |
| `prehtml` | `lay_html.php`, before final HTML output — debug injection happens here |
| `posthtml` | Runtime, after `$content['html']` is set |
| `prerelocate` | `relocate()`, before a redirect |
| `setlink` | `setlink()`, allows plugins to override link targets |
| `error` | `fbx_error()`, when a fatal error occurs |

### Included Plugins (skeleton)

- **debug.php**: Defines `debug()` for user code. Collects messages into `$fbx['user_debug_buffer']`. On `prehtml`, formats both internal and user buffers into `$content['debugpanes']`.
- **profiler.php**: Records enter/exit times on all pre/post control/query/action/display/layout phases. On `prehtml`, renders a sorted timing table into `$content['debugpanes']['profiler']`.
- **queries.php**: Provides `mysqli_safe_query()` wrapper that logs queries with timing. On `prehtml`, renders query log into `$content['debugpanes']['queries']`. Handles lazy DB connection.
- **menu.php**: On `prehtml`, renders an admin menu into `$content['debugpanes']['menu']`. Provides `fbx_menu_register($title, $action)` for apps to add menu items.

---

## Global Variables

### `$fbx` (Framework State)

| Key | Type | Description |
|---|---|---|
| `$fbx['version']` | string | Framework version |
| `$fbx['fbx_root']` | string | Absolute path to firebox directory (trailing slash) |
| `$fbx['site_root']` | string | Absolute path to project directory (trailing slash) |
| `$fbx['production']` | bool | Production mode flag |
| `$fbx['action']` | string | Current action e.g. `todo.show_list` |
| `$fbx['controller']` | string | Current controller name |
| `$fbx['method']` | string | Current method name |
| `$fbx['settings']` | array | Loaded from `settings.php` |
| `$fbx['debug_buffer']` | array | Framework debug messages (`fbx_debug()`) |
| `$fbx['user_debug_buffer']` | array | User debug messages (`debug()`) |
| `$fbx['compiled']` | array | Files compiled this request (dedup guard) |
| `$fbx['call_stack']` | array | Stack of active `{filename, method}` entries |
| `$fbx['links']` | array | Named links registered via `setlink()` |
| `$fbx['plugins']` | array | Registered plugin callbacks by phase |
| `$fbx['files_run']` | array | Model/view files run this request (`action_once` tracking) |
| `$fbx['libs']` | array | Map of lib function names to source files |
| `$fbx['debug_indent']` | int | Current debug indentation level |

### `$content` (Page Data)

| Key | Description |
|---|---|
| `$content['body']` | Main rendered page content (set by controller methods) |
| `$content['html']` | Final HTML (set after `posthtml` plugins run) |
| `$content['debugpanes']` | Assoc array of debug pane name => HTML content |
| `$content['includes']` | HTML to inject into `<head>` |

### `$myself`
Base URL string ending with `?go=`. Used to build action links: `$myself . 'controller.method'`.

---

## Settings Reference

```php
$fbx['settings'] = array(
    'name'                => 'App Name',           // used in default layout <title>
    'password'            => 'secret',             // unlocks dev features via ?fbx_pass=
    'default_action'      => 'ctrl.method',        // action when ?go= is absent
    'development_plugins' => array('debug', ...),  // plugins loaded in dev mode
    'production_plugins'  => array(...),            // plugins loaded in production
    'pre'                 => array('ctrl.method'), // global pre-actions (run before every request)
    'post'                => array('ctrl.method'), // global post-actions (run after every request)
    'mysqli_host'         => 'localhost',
    'mysqli_database'     => 'mydb',
    'mysqli_user'         => 'user',
    'mysqli_pass'         => 'pass',
);
```

---

## Public API (Controller/Model/View Code)

| Function | Description |
|---|---|
| `control($action, ...$args)` | Execute a controller method. Returns its output. |
| `query($filename, ...$args)` | Execute a model query. Returns its return value. |
| `action($filename, ...$args)` | Execute a model action. Returns its return value. |
| `action_once($filename, ...$args)` | Execute a model action at most once per request. |
| `display($filename, ...$args)` | Render a view. Returns HTML string. |
| `layout($filename, ...$args)` | Render a layout. Returns HTML string. |
| `setlink($name, $action)` | Register a named link. Action can be `controller.method` or just `method` (relative). |
| `linkto($name)` | Get the URL for a named link. |
| `linkaction($name)` | Get the `controller.method` string for a named link. |
| `relocate($url)` | Redirect to URL and exit. Falls back to JS redirect if headers already sent. |
| `debug($msg, __FILE__, __LINE__)` | Log a user debug message (requires debug plugin). |

---

## Testing

Run the test suite with:

```bash
php tests/run_tests.php          # all tests (compiler + HTTP)
php tests/run_tests.php compiler # compiler unit tests only (no server needed)
php tests/run_tests.php http     # HTTP integration tests only (requires server)
```

HTTP tests hit `http://10.0.0.10/firebox-test/` and require the testproject to be deployed first (`bash deploy.sh`).

### Test structure

```
tests/
  run_tests.php           Main runner
  helpers.php             Assertion library
  bootstrap.php           Minimal compiler bootstrap for CLI tests
  compiler/
    test_lexer.php        Lexer tokenization (state machine, all token types)
    test_blocks.php       Block detector (function detection, depth tracking)
    test_references.php   Function reference detector
    test_compile.php      Full compilation pipeline, caching, lib scanning
  http/
    test_routing.php      Action routing, production mode, URL options
    test_lifecycle.php    pre/post/action_once, plugins, setlink, redirect
    test_errors.php       Error handling, test failures, @ suppression
```

### Adding tests to user code

The framework automatically runs test functions in dev mode. Name them to match the compiled function pattern:

- Controller method `show_list` → define `function test_show_list()` in the same controller file
- Model file `qry_s_items.php` → define `function test_qry_s_items()` in the same file
- Test functions must return `true` to pass or `false` to fail. A failing test aborts the request with an error.

---

## Known Limitations

These are design constraints of the compiler, not fixable bugs:

1. **No OOP support in user files**: Classes, interfaces, traits, and enums in controller/model/view files will compile incorrectly. The compiler's block detector does not recognise `class` as a block keyword. Methods inside classes will be detected as top-level functions and renamed incorrectly. Firebox is designed for procedural PHP.

2. **No namespace support**: `namespace` and `use` statements in user files are not understood by the compiler. Namespaced function calls won't match lib function lookup.

3. **Anonymous functions / closures** have unpredictable compiled names. They work in simple cases (the `function_exists()` guard prevents crashes) but are not reliable inside controllers. Use named helper functions instead.

4. **Flat-file PHP data store requires OPcache invalidation**: If you use PHP files as a data store (as in the todo demo), call `opcache_invalidate($file, true)` after every write, otherwise PHP's OPcache will serve stale data on the next read.

5. **No built-in session management, CSRF protection, or authentication**: these are application-level concerns outside the framework's scope.
