# Firebox - Claude Technical Notes

## Project Status (2026-05-10)

Starting a full cleanup/debug/test/documentation pass. Goal: 100% working, edge cases documented, review-ready code, full test coverage. Strategy: refactor existing files (not rewrite), fix bugs in order, add tests as we go.

Documentation pass is complete (README.md, DEVELOPER.md, this file). Next: fix bugs in priority order, add test harness.

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
```

---

## Compiled Function Name Conventions

| Source | Compiled name |
|---|---|
| `controller/foo.php` → `bar()` | `controller_foo_bar` |
| `controller/foo.php` → `test_bar()` | `test_controller_foo_bar` |
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

---

## Known Bugs (prioritized)

### High priority (correctness)

**BUG-1: `fbx_load_libs()` path mismatch** (`firebox_compiler.php:485-495`)
`fbx_load_libs()` looks for cached lib files at `parsed/[empty]lib.name.php`.
`fbx_get_function_names()` writes them to `parsed/dev/lib.name.php` or `parsed/prod/lib.name.php`.
The cache check always misses → lib directory rescanned every request.
Fix: Change `fbx_load_libs()` to check `parsed/dev/` or `parsed/prod/` based on `$fbx['production']`.

**BUG-2: `$type`/`$typeindex` uninitialized in `fbx_get_blocks_from_lex()`** (`firebox_compiler.php:421`)
When `{` appears without a recognized preceding keyword (array literals, match expressions, anonymous classes), the backward scan reaches index 0 without breaking, and `$type`/`$typeindex` hold whatever was set by the previous iteration (or are undefined on first `{`). The resulting block entry has a garbage type.
This doesn't cause a crash because the compile step only acts on blocks with `type == 'function'`, but it's fragile.
Fix: Initialize `$type = ''` and `$typeindex = -1` before the backward scan; skip `$blocks[]` push if `$type` is empty.

**BUG-3: Closing-brace indentation constraint** (noted in `skeleton/todo.php:7`)
The backward scan in `fbx_get_blocks_from_lex()` finds the keyword preceding `{` by scanning backward through lexemes. It uses `$word` to track the last seen `word` lexeme. The issue is that `$type` and `$typeindex` are set inside the loop and break immediately — but if the `}` is indented (preceded by spaces), the space token appears as the last lexeme before `}`, which doesn't affect the scan. The real issue may be more subtle.
Needs: A test case that reproduces the failure before fixing.

**BUG-4: `fbx_compile()` uses relative file paths** (`firebox_compiler.php:54,65`)
`file($filename)` and `filemtime($filename)` receive relative paths like `controller/foo.php`.
If CWD ≠ site_root, these fail silently or read the wrong file.
Fix: Prepend `$fbx['site_root']` to the input file read and mtime check.

### Medium priority (robustness)

**BUG-5: `@include('settings.php')` masks parse errors** (`firebox_runtime.php:89`)
Fix: Remove `@`, add a `file_exists()` check first, emit a clear error if missing.

**BUG-6: PHP 8 `count()` on non-array** (multiple locations in `firebox_runtime.php`)
`count($fbx['settings'])`, `count($fbx['settings']['production_plugins'])`, etc. will throw `TypeError` in PHP 8 if keys are missing.
Fix: Use `isset()` guards before `count()` calls.

**BUG-7: `fbx_load_libs()` parameter ignored** (`firebox_compiler.php:474`)
Called with a path argument but the function ignores it and uses `$fbx['site_root']` directly.
Fix: Remove the parameter from the call site (line 38), keep the function as-is.

### Low priority (hygiene)

**BUG-8: E_NOTICE suppression** (`firebox_runtime.php:67`)
`if ($errno == 8) return;` silences all PHP notices globally.
Fix: Remove this line after fixing the underlying code that generates notices. Track down with `fbx_skip_tests` + debug mode.

**BUG-9: XSS in `relocate()` JS fallback** (`firebox_controller_functions.php:58`)
`document.location.href='$url'` — `$url` is not JS-escaped.
Fix: `addslashes($url)` or `json_encode($url)` for the JS string.

**BUG-10: `register_shutdown_function` commented out** (`firebox_runtime.php:77`)
Fix: Uncomment after verifying `fbx_error_shutdown()` handles all error types correctly. The current implementation calls `die()` with a plain string — good enough for now.

---

## Test Strategy

### Compiler tests
- Unit test `fbx_lexical_parser()` on small PHP snippets: verify token types and order.
- Unit test `fbx_get_blocks_from_lex()`: verify block detection on known inputs.
- Integration test `fbx_compile()`: compile a sample controller, verify output file content.
- Test pre/post inlining: verify variables flow correctly across pre→method→post.
- Test function renaming: verify all name patterns are correct.

### Runtime/routing tests
- Test action resolution: `?go=ctrl.method`, `?go=fbx.welcome`, missing `?go`, default_action.
- Test `control()`: correct function called, parameters passed, return value propagated.
- Test `fbx_run_file()`: query, action, action_once (dedup), display (return vs echo).
- Test `setlink()`/`linkto()`/`linkaction()`: relative and absolute actions.
- Test plugin phases fire in order.

### CLI/headless mode
- `lay_html.php` checks `$_SERVER['argv']` to output plain text instead of HTML. This enables headless testing. Worth documenting and verifying.

---

## Decisions

- **Refactor, not rewrite**: The lexer/compiler is the most complex part and the existing code is the only spec. Preserving it and fixing bugs is safer than rewriting.
- **Fix bugs in dependency order**: BUG-1 and BUG-4 affect every compile operation, so fix those first.
- **No new abstractions**: Don't add namespaces, classes, or Composer during this cleanup pass. Keep the flat-function style consistent.
