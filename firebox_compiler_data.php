<?php

/* Firebox - PHP Web Application Framework
   Copyright (C) 2007 Andrew J. Fabian
   
   Andrew J. Fabian can be reached via email at afabian@anjero.com, or at this address:
   209 S. Knollwood Dr.
   Suite 1301
   Blacksburg, VA 24060

   This program is free software: you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License
   along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

global $fbx;

// these are lists of stopping points for various states parts of the file
	
// if just a string, match element, no state change
// if array: element, new state or "POP", optional which preg_match match index to keep

$fbx['lex']['html']['o_php1'] = array('<?php', 'php');
$fbx['lex']['html']['o_php2'] = array('<?', 'php');
$fbx['lex']['html']['html'] = array('/((.|\n)+?)(\<\?|$)/', false, 1);

$fbx['lex']['php']['c_php'] = array('?>', 'POP');

$fbx['lex']['php']['space'] = '/[ \t\n\r\f]+/';
$fbx['lex']['php']['exponent1'] = '/[0-9]+[eE][+-]?[0-9]+/';
$fbx['lex']['php']['exponent2'] = '/[0-9]*[\.][0-9]+[eE][+-]?[0-9]+/';
$fbx['lex']['php']['exponent3'] = '/[0-9]+[\.][0-9]*[eE][+-]?[0-9]+/';
$fbx['lex']['php']['float1'] = '/[0-9]*[\.][0-9]+/';
$fbx['lex']['php']['float2'] = '/[0-9]+[\.][0-9]*/';
$fbx['lex']['php']['int'] = '/[0-9]+/';

$fbx['lex']['php']['word'] = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';

$fbx['lex']['php']['dollar'] = '$';
$fbx['lex']['php']['semi'] = ';';

$fbx['lex']['php']['o_curly'] = '{';
$fbx['lex']['php']['c_curly'] = '}';
$fbx['lex']['php']['o_paren'] = '(';
$fbx['lex']['php']['c_paren'] = ')';
$fbx['lex']['php']['o_bracket'] = '[';
$fbx['lex']['php']['c_bracket'] = ']';

$fbx['lex']['php']['linecomment'] = array('/\/\//', 'linecomment');
$fbx['lex']['php']['o_comment'] = array('/\/\*/', 'comment');

$fbx['lex']['php']['equaltype'] = '===';
$fbx['lex']['php']['notequaltype'] = '!==';
$fbx['lex']['php']['equal'] = '==';
$fbx['lex']['php']['notequal'] = '!=';

$fbx['lex']['php']['inc'] = '++';
$fbx['lex']['php']['dec'] = '--';

$fbx['lex']['php']['keyvalue'] = '=>';
$fbx['lex']['php']['method'] = '->';

$fbx['lex']['php']['concatto'] = '.=';
$fbx['lex']['php']['addto'] = '+=';
$fbx['lex']['php']['subto'] = '-=';
$fbx['lex']['php']['multto'] = '*=';
$fbx['lex']['php']['divto'] = '\\=';
$fbx['lex']['php']['modto'] = '%=';
$fbx['lex']['php']['bitandto'] = '&=';
$fbx['lex']['php']['bitorto'] = '|=';
$fbx['lex']['php']['bitxorto'] = '^=';
$fbx['lex']['php']['bitlshiftto'] = '<<=';
$fbx['lex']['php']['bitrshiftto'] = '>>=';

$fbx['lex']['php']['concat'] = '.';
$fbx['lex']['php']['add'] = '+';
$fbx['lex']['php']['sub'] = '-';
$fbx['lex']['php']['mult'] = '*';
$fbx['lex']['php']['div'] = '/\//';
$fbx['lex']['php']['mod'] = '%';

$fbx['lex']['php']['heredoc'] = array('<<<', 'heredocident');

$fbx['lex']['php']['bitlshift'] = '<<';
$fbx['lex']['php']['bitrshift'] = '>>';

$fbx['lex']['php']['lessequal'] = '<=';
$fbx['lex']['php']['less'] = '<';
$fbx['lex']['php']['greaterequal'] = '>=';
$fbx['lex']['php']['greater'] = '>';

$fbx['lex']['php']['assign'] = '=';

$fbx['lex']['php']['and'] = '&&';
$fbx['lex']['php']['or'] = '||';
$fbx['lex']['php']['reference'] = '&';
$fbx['lex']['php']['bitand'] = '&';
$fbx['lex']['php']['not'] = '!';
$fbx['lex']['php']['bitxor'] = '^';
$fbx['lex']['php']['bitor'] = '|';

$fbx['lex']['php']['question'] = '?';
$fbx['lex']['php']['scope'] = '::';
$fbx['lex']['php']['colon'] = ':';
$fbx['lex']['php']['comma'] = ',';

$fbx['lex']['php']['singlequote'] = array("'", 'singlequote');
$fbx['lex']['php']['doublequote'] = array('"', 'doublequote');
$fbx['lex']['php']['backtickquote'] = array('`', 'backtickquote');

$fbx['lex']['linecomment']['newline'] = array("\n", 'POP');

$fbx['lex']['comment']['c_comment'] = array('*/', 'POP');
$fbx['lex']['comment']['text'] = array('/((.|\n)+?)\*?\//', false, 1);

$fbx['lex']['heredocident']['heredocident'] = array('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', 'heredoc');

$fbx['lex']['heredoc']['text'] = false;     // this is updated by the parser whenever it finds a heredocident
$fbx['lex']['heredoc']['endofdoc'] = false; // this is updated by the parser whenever it finds a heredocident

$fbx['lex']['singlequote']['text'] = '/[^\\\\\']+/';
$fbx['lex']['singlequote']['escape'] = array('\\', 'singlequoteescape');
$fbx['lex']['singlequote']['singlequote'] = array("'", 'POP');

$fbx['lex']['singlequoteescape']['quote'] = array("'", 'POP');
$fbx['lex']['singlequoteescape']['backslash'] = array('\\', 'POP');
$fbx['lex']['singlequoteescape']['text'] = array('/./', 'POP'); // anything else isn't an escape code

$fbx['lex']['doublequote']['text'] = '/[^"\\\${]+/';
$fbx['lex']['doublequote']['doublequote'] = array('"', 'POP');
$fbx['lex']['doublequote']['escape'] = array('\\', 'doublequoteescape');
$fbx['lex']['doublequote']['dollar'] = array('$', 'doublequotevar');
$fbx['lex']['doublequote']['o_curly'] = array('{', 'doublequotecurly');

$fbx['lex']['doublequoteescape']['hex'] = array('/x[0-9A-Fa-f]{1,2}/', 'POP');
$fbx['lex']['doublequoteescape']['octal'] = array('/[0-7]{1-3}/', 'POP');
$fbx['lex']['doublequoteescape']['linefeed'] = array('n', 'POP');
$fbx['lex']['doublequoteescape']['return'] = array('r', 'POP');
$fbx['lex']['doublequoteescape']['tab'] = array('t', 'POP');
$fbx['lex']['doublequoteescape']['backslash'] = array('\\', 'POP');
$fbx['lex']['doublequoteescape']['dollar'] = array('$', 'POP');
$fbx['lex']['doublequoteescape']['doublequote'] = array('"', 'POP');
$fbx['lex']['doublequoteescape']['text'] = array('/./', 'POP'); // anything else isn't an escape code

$fbx['lex']['doublequotevar']['o_curly'] = array('{', 'doublequotevarcurly');
$fbx['lex']['doublequotevar']['word'] = array('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', 'POP');

$fbx['lex']['doublequotevarcurly']['word'] = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';
$fbx['lex']['doublequotevarcurly']['c_curly'] = array('}', 'POP');

$fbx['lex']['doublequotecurly']['dollar'] = '$';
$fbx['lex']['doublequotecurly']['word'] = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';
$fbx['lex']['doublequotecurly']['o_bracket'] = '[';
$fbx['lex']['doublequotecurly']['c_bracket'] = ']';
$fbx['lex']['doublequotecurly']['method'] = '->';
$fbx['lex']['doublequotecurly']['c_curly'] = array('}', 'POP');
$fbx['lex']['doublequotecurly']['o_curly'] = array('{', 'doublequotecurly');
$fbx['lex']['doublequotecurly']['text'] = array('/./', 'POP'); // anything else isn't a special variable

$fbx['lex']['backtickquote']['text'] = '/[^`\\\${]+/';
$fbx['lex']['backtickquote']['backtickquote'] = array('`', 'POP');
$fbx['lex']['backtickquote']['escape'] = array('\\', 'doublequoteescape');
$fbx['lex']['backtickquote']['dollar'] = array('$', 'doublequotevar');
$fbx['lex']['backtickquote']['o_curly'] = array('{', 'doublequotecurly');

$fbx['lex']['backtickquoteescape']['hex'] = array('/x[0-9A-Fa-f]{1,2}/', 'POP');
$fbx['lex']['backtickquoteescape']['octal'] = array('/[0-7]{1-3}/', 'POP');
$fbx['lex']['backtickquoteescape']['linefeed'] = array('n', 'POP');
$fbx['lex']['backtickquoteescape']['return'] = array('r', 'POP');
$fbx['lex']['backtickquoteescape']['tab'] = array('t', 'POP');
$fbx['lex']['backtickquoteescape']['backslash'] = array('\\', 'POP');
$fbx['lex']['backtickquoteescape']['dollar'] = array('$', 'POP');
$fbx['lex']['backtickquoteescape']['backtickquote'] = array('`', 'POP');
$fbx['lex']['backtickquoteescape']['text'] = array('/./', 'POP'); // anything else isn't an escape code

$fbx['lex']['backtickquotevar']['o_curly'] = array('{', 'doublequotevarcurly');
$fbx['lex']['backtickquotevar']['word'] = array('/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/', 'POP');

$fbx['lex']['backtickquotevarcurly']['word'] = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';
$fbx['lex']['backtickquotevarcurly']['c_curly'] = array('}', 'POP');

$fbx['lex']['backtickquotecurly']['dollar'] = '$';
$fbx['lex']['backtickquotecurly']['word'] = '/[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*/';
$fbx['lex']['backtickquotecurly']['o_bracket'] = '[';
$fbx['lex']['backtickquotecurly']['c_bracket'] = ']';
$fbx['lex']['backtickquotecurly']['method'] = '->';
$fbx['lex']['backtickquotecurly']['c_curly'] = array('}', 'POP');
$fbx['lex']['backtickquotecurly']['o_curly'] = array('{', 'doublequotecurly');
$fbx['lex']['backtickquotecurly']['text'] = array('/./', 'POP'); // anything else isn't a special variable

$counter = 0;
foreach ($fbx['lex'] as $key => $values) $counter += count($values);
fbx_debug("Finished loading lex rules: $counter rules in " . count($fbx['lex']) . " states", __FILE__, __LINE__);
