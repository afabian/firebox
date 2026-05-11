# Firebox - Claude Technical Notes

## Project Status (2026-05-10)

**Cleanup/debug/test/documentation pass complete.** All known bugs fixed, full test suite written and passing (186 tests), all documentation updated. Framework is production-ready for procedural PHP apps.

### Recent changes (post-cleanup)

- **Short tag upgrade**: `<?` in compiled output is now always emitted as `<?php`. `<?=` (always-on) preserved verbatim as a distinct `o_php_echo` token. Compiled files no longer require `short_open_tag = On`.
- **Precise mtime tracking**: After compiling, the output file's mtime is set to exactly match the source file's mtime via `touch($outputfile, filemtime($sourcefile))`. The timestamp check (`filemtime(source) <= filemtime(output)`) now means "compiled from this exact version."
- **Prod cache clearing**: `start_production.php` now deletes all `parsed/prod/*.php` files before recompiling. Prevents stale compiled files from surviving a source rename or deletion.

---

## File Map

```
firebox_runtime.php          Request lifecycle, routing, error handling, fbx_debug, fbx_url_option, fbx_execute_plugins
firebox_controller_functions.php  Public API: control(), query(), action(), action_once(), display(), layout(), setlink(), linkto(), linkaction(), relocate(), fbx_run_file()
firebox_compiler.php         fbx_compile(), fbx_lexical_parser(), fbx_lexical_parser_next_lexeme(), fbx_get_blocks_from_lex(), fbx_get_function_references_from_lex(), fbx_load_libs(), fbx_get_function_names(), fbx_dir_tree_files()
firebox_compiler_data.php    Lexer state machine rules — populates $fbx['lex']
admin/welcome.php            Shown when no project exists (fbx.welcome)
admin/error.php              Error display page
admin/about.php              About page (fbx.about)
admin/create_skeleton.php    Creates blank project (fbx.create_skeleton)
admin/create_todo.php        Creates demo todo app (fbx.create_todo)
admin/start_production.php   Creates parsed/production file (fbx.start_production)
admin/stop_production.php    Removes parsed/production file (fbx.stop_production)
skeleton/                    Demo todo app source files + plugin implementations
testproject/                 Runnable todo demo app (deployed to server for testing)
tests/                       Test suite (compiler unit tests + HTTP integration tests)
deploy.sh                    Rsyncs framework + testproject to 10.0.0.10
```

---

## Compiled Function Name Conventions

| Source | Compiled name |
|---|---|
| `controller/foo.php` → `bar()` | `controller_foo_bar` |
| `controller/foo.php` → `test_bar()` | `test_controller_foo_bar` — note: methods named `test_*` are treated as test functions, not callable methods |
| `model/foo/qry_thing.php` → `qry_thing()` | `model_foo_qry_thing_qry_thing` |
| `model/foo/qry_thing.php` → `test_qry_thing()` | `test_model_foo_qry_thing_qry_thing` |
| `view/foo/my_view.php` → `my_view()` | `view_foo_my_view_my_view` |
| `layout/foo/my_layout.php` → `my_layout()` | `layout_foo_my_layout_my_layout` |

Controller compiled output file: `parsed/dev/controller.foo.php`
Model compiled output file: `parsed/dev/model.foo.qry_thing.php`
View compiled output file: `parsed/dev/view.foo.my_view.php`

---

## Pre/Post Inlining Mechanics

In `fbx_compile()`:

1. `pre()` body extracted: everything between its opening `{` and closing `}` exclusive.
2. `post()` body extracted similarly.
3. For each renamed function, the opening `{` lexeme is replaced with:
   ```
   {\n\tglobal $fbx, $content;\n[require_once lib lines]\n[pre body]\n
   ```
4. The closing `}` lexeme is replaced with:
   ```
   \n[post body]\n}}
   ```
   The double `}}` closes both the function and the wrapping `if (!function_exists(...)) {`.
5. The original `pre()` and `post()` function definitions are excluded from output by index range.

The `pre()` and `post()` contents share scope with the method body — variables set in `pre()` are available in the method, and variables set in the method are available in `post()`. This is intentional.

---

## Lexer State Machine

States: `html`, `php`, `singlequote`, `singlequoteescape`, `doublequote`, `doublequoteescape`, `doublequotevar`, `doublequotevarcurly`, `doublequotecurly`, `backtickquote`, `backtickquoteescape`, `backtickquotevar`, `backtickquotecurly`, `comment`, `linecomment`, `heredocident`, `heredoc`

Stack-based: PUSH on entering a sub-state, POP on exit. POP2 available for double-pop (used in heredoc exit).

Heredoc: when `heredocident` is matched, the identifier text is used to dynamically update the `heredoc` state's `text` and `endofdoc` patterns.

`fbx_lexical_parser_next_lexeme()`: finds the earliest match across all patterns for the current state. Pattern can be a plain string (strpos) or a regex (preg_match). The 3rd element of an array rule is the capture group index to use as the matched content.

Notable tokens: `@` is `error_suppress`, `~` is `bitnot`, `\` is `ns_sep`. These were missing in the original and were added during the cleanup pass — without them, these characters would be silently dropped from compiled output.

---

## Bugs Fixed During Cleanup Pass

All of these were fixed. Listed for historical context.

| Bug | Fix |
|---|---|
| `fbx_load_libs()` had dead cache check that always missed | Removed outer check; `fbx_get_function_names()` handles caching internally |
| `fbx_compile()` used relative paths for file I/O | Prepend `$fbx['site_root']` to `$filename` for `file()` and `filemtime()` |
| `$type`/`$typeindex` uninitialized in block detector | Reset to null before each backward scan |
| Closing-brace indentation broke compilation | Fixed operator precedence bug in pre/post check + `name` key always set in blocks |
| `@include('settings.php')` masked errors | Absolute path + `file_exists()` guard + `ParseError` catch |
| `count()` on settings arrays crashed PHP 8 | Replaced with `?? []` null-coalescing |
| XSS in `relocate()` JS fallback | `json_encode($url, JSON_HEX_TAG)` |
| `register_shutdown_function` commented out | Re-enabled with fatal-type allowlist |
| Short tags (`<?`) in admin/skeleton files broke PHP 8 (short_open_tag=Off) | Converted to `<?php` |
| `<? global $fbx, $content; ?>` leaking into HTML output | Changed to `<?php` |
| `@` operator missing from lexer — dropped from compiled output | Added `error_suppress` token |
| `@` detection in error handler used `=== 0` (PHP 7 behavior) | Changed to `!(error_reporting() & $errno)` (PHP 8+) |
| `@$_REQUEST['go']` caused E_WARNING in PHP 8 | Changed to `$_REQUEST['go'] ?? ''` |
| Blanket E_NOTICE suppression (`$errno == 8`) | Removed |
| `create_skeleton.php` used `$_REQUEST['target']` without null check | Added `isset()` |
| `create_skeleton.php` passed `null` to `mkdir()` mode | Changed to `0755` |
| OPcache caching PHP flat-file data store after writes | Added `opcache_invalidate()` in todo write queries |

---

## Test Suite

`php tests/run_tests.php [compiler|http|all]`

186 tests total. Compiler tests require no server. HTTP tests require `bash deploy.sh` first and server at 10.0.0.10.

Key test fixtures in testproject:
- `controller/fbxtest.php` — pre/post/action_once/setlink/linkaction lifecycle tests
- `controller/fbxtest_at.php` — @ operator survives compilation
- `controller/fbxtest_fail.php` — deliberately failing test_ function
- `model/fbxtest/fbxtest_init.php` — call counter for action_once test

---

## Known Limitations (unfixable without redesign)

1. **No OOP**: Classes, interfaces, traits in user files compile incorrectly. The block detector doesn't recognize `class` as a keyword; methods inside classes get renamed as top-level functions.

2. **No namespaces**: `namespace`/`use` not understood by compiler.

3. **Closures/anonymous functions**: Compile with unpredictable names in edge cases. The `function_exists()` guard prevents crashes but reliability is not guaranteed.

4. **Flat-file PHP data store + OPcache**: Requires explicit `opcache_invalidate()` after every write.

---

## Deployment

`bash deploy.sh` from the firebox repo root:
- Rsyncs framework files to `10.0.0.10:/var/www/html/firebox/`
- Rsyncs testproject to `10.0.0.10:/var/www/html/firebox-test/`
- Creates `parsed/dev/` and `parsed/prod/` with 777 permissions
- Sets `todo.data` to 666 (writable by www-data)

Test app: `http://10.0.0.10/firebox-test/`
