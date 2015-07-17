<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

defined('MOODLE_INTERNAL') || die();

// easy loading for Kaltura libraries
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ . '/vendors/');
spl_autoload_register(
        function ($classname) {
            if (strpos($classname, 'Kaltura\\Client\\') === 0) {
                $path = str_replace('\\', '/', $classname) . '.php';
                if (include ($path)) {
                    return TRUE;
                }
            }
        });

// just in case...
global $CFG;
require_once $CFG->dirroot . '/repository/lib.php';

// load helpers
require_once __DIR__ . '/helpers/utils.php';

// Initialize IntraLibrary
require_once __DIR__ . '/vendors/IntraLibrary-PHP/src/IntraLibrary/Loader.php';
\IntraLibrary\Loader::register();

$config = repository_intralibrary_config();
\IntraLibrary\Configuration::set($config);
\IntraLibrary\Configuration::set('timeout',
    isset($CFG->intraLibrary_timeout) ? $CFG->intraLibrary_timeout : 5000);

$ilCache = cache::make('repository_intralibrary', 'app_cache');
\IntraLibrary\Cache::register('load', array($ilCache, 'get'));
\IntraLibrary\Cache::register('save', array($ilCache, 'set'));

\IntraLibrary\Debug::register('log', 'repository_intralibrary_log');
\IntraLibrary\Debug::register('screen',
        function ($string) {
            $string = htmlspecialchars_decode($string);
            repository_intralibrary_log($string);
        });

define('INTRALIBRARY_AUTH_OPEN',        'open');
define('INTRALIBRARY_AUTH_OPEN_TOKEN',  'open_token');
define('INTRALIBRARY_AUTH_SHARED',      'shared');
define('KALTURA_VIDEO_PREFIX',          'http://intralibrary-kaltura-filter/');
define('KALTURA_FILE_EXTENSIONS',       'flv,asf,qt,mov,mpg,avi,wmv,mp4,3gp,f4v,m4v');

