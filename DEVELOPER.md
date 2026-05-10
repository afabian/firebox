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
- Test function name: `test_controller_name_method` (defined in the same file)
- **Known constraint:** The closing `}` of each function must be the first character on its line (compiler bug — see Known Issues).

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
    $x = 1;           // <-- pre() body inlined
    echo $x;
    return layout('main');  // <-- post() body inlined
}}
```

### Caching

- Compiled files are cached by mtime: if `parsed/dev/file.php` is newer than the source, compilation is skipped.
- In production mode, compilation is never triggered — only the cached `parsed/prod/` files are used.
- `$fbx['compiled']` tracks files already compiled this request to avoid double-compilation.

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
    'name'                => 'App Name',          // used in default layout <title>
    'password'            => 'secret',            // unlocks dev features via ?fbx_pass=
    'default_action'      => 'ctrl.method',       // action when ?go= is absent
    'development_plugins' => array('debug', ...),  // plugins loaded in dev mode
    'production_plugins'  => array(...),           // plugins loaded in production
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

## Known Issues / Bugs

1. **Compiler closing-brace constraint** (`todo.php` comment): The closing `}` of each function in a controller must be the first character on its line. The block detector scans lexemes backward when it sees `{`, and something about indented closing braces confuses it. Root cause is in `fbx_get_blocks_from_lex` — the backward scan for block type is fragile.

2. **Relative path in `fbx_compile()`**: `file($filename)` and `filemtime($filename)` use the path as-is (e.g. `controller/foo.php`). These succeed only if PHP's CWD is the site root at the time of the call. Not guaranteed under all web server configurations.

3. **`fbx_load_libs()` path mismatch**: `fbx_load_libs()` checks for cached lib index files at `parsed/[empty]lib.name.php` (no `dev/`/`prod/` subdirectory), but `fbx_get_function_names()` writes them to `parsed/dev/lib.name.php` or `parsed/prod/lib.name.php`. The check in `fbx_load_libs()` therefore always misses the cache, causing re-scan every request.

4. **`fbx_load_libs()` signature mismatch**: Defined as `fbx_load_libs()` (no args) but called as `fbx_load_libs($fbx['site_root'] . 'lib/')`. The argument is silently ignored; the function uses the global `$fbx['site_root']` directly.

5. **`$type`/`$typeindex` uninitialized in `fbx_get_blocks_from_lex()`**: If `{` appears without a preceding keyword (e.g. in an array literal `array()` or a class body), the backward scan finds no match and `$type`/`$typeindex` remain set from the previous iteration (or undefined on first occurrence). This can produce incorrect block entries.

6. **`register_shutdown_function` commented out** (`firebox_runtime.php:77`): Fatal parse errors and OOM errors won't display a clean error page — PHP will output a raw fatal error or blank page.

7. **`@include('settings.php')` suppresses parse errors**: If `settings.php` has a syntax error, it silently fails and `$fbx['settings']` will be undefined, causing downstream errors with no clear message.

8. **`E_NOTICE` (errno 8) suppressed globally**: The error handler silently drops all PHP notices. This hides undefined variable warnings that would otherwise catch real bugs.

9. **XSS in `relocate()`**: The JavaScript fallback redirect uses `$url` unescaped inside a JS string: `document.location.href='$url'`. If `$url` contains a single quote, it breaks the JS. Not exploitable for cross-site attacks in normal use (URL comes from `linkto()`), but worth fixing.

10. **`count()` on non-array in PHP 8**: Several places call `count($fbx['settings'])`, `count($fbx['settings']['production_plugins'])`, etc. without checking if the key exists first. PHP 8 throws a `TypeError` for `count(null)`.

11. **Debug buffer capped at 1000 entries** (`firebox_runtime.php:217`): `array_shift` is used to trim the buffer, which is O(n) on every message after 1000. Not a correctness issue but a performance note.

12. **`fbx_error()` in admin context uses skeleton layout**: When a `fbx.*` or `plugin.*` action triggers `fbx_error()`, the error page still tries to use `skeleton/lay_html.php`. If the project has no skeleton directory, this fails.

---

## Todo

- [ ] Fix closing-brace compiler constraint (issue #1)
- [ ] Fix `fbx_compile()` relative path issue (issue #2)
- [ ] Fix `fbx_load_libs()` path/signature mismatch (issues #3, #4)
- [ ] Fix uninitialized `$type` in block detector (issue #5)
- [ ] Re-enable or remove `register_shutdown_function` (issue #6)
- [ ] Fix `@include` on settings.php (issue #7)
- [ ] Remove blanket E_NOTICE suppression (issue #8)
- [ ] Fix XSS in `relocate()` JS fallback (issue #9)
- [ ] Add PHP 8 `count()` guards (issue #10)
- [ ] Write test suite for the compiler
- [ ] Write test suite for the runtime/routing
- [ ] Add `parsed/dev/` and `parsed/prod/` to `.gitignore`
- [ ] Document headless/CLI mode (currently supported via `$_SERVER['argv']` check in `lay_html.php`)
