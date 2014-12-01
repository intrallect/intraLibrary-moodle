<?php
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
require_once __DIR__ . '/helpers/auth.php';
require_once __DIR__ . '/helpers/logger.php';

// Initialize IntraLibrary
require_once __DIR__ . '/vendors/IntraLibrary-PHP/src/IntraLibrary/Loader.php';
\IntraLibrary\Loader::register();

$config = repository_intralibrary_config();
\IntraLibrary\Configuration::set($config);
\IntraLibrary\Configuration::set('timeout', $CFG->intraLibrary_timeout);

// Try to use Memcache for caching, then fall back on APC
$memcache = repository_intralibrary_get_memcache();
if ($memcache) {
    $memcache_ns = 'moodle/intralibrary/';
    $cacheLoad = function ($key) use($memcache, $memcache_ns) {
            return $memcache->get($memcache_ns . $key);
    };
    $cacheSave = function ($key, $value, $expires = 0) use($memcache, $memcache_ns) {
        return $memcache->set($memcache_ns . $key, $value, 0, $expires);
    };
} else if (function_exists('apc_fetch') && function_exists('apc_store')) {
    $cacheLoad = 'apc_fetch';
    $cacheSave = 'apc_store';
} else {
    $cacheLoad = $cacheSave = function () {
        return FALSE;
    };
}
\IntraLibrary\Cache::register('load', $cacheLoad);
\IntraLibrary\Cache::register('save', $cacheSave);

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

