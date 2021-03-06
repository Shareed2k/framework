<?php
/**
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

/**
 * Simple functions of framework
 * be careful with this way
 * @author   Anton Shevchuk
 * @created  07.09.12 11:29
 */
if (!function_exists('debug')) {
    /**
     * Debug variables
     *
     * @return void
     */
    function debug()
    {
        // check definition
        if (!defined('DEBUG') or !DEBUG) {
            return;
        }

        ini_set('xdebug.var_display_max_children', 512);

        if ('cli' == PHP_SAPI) {
            if (extension_loaded('xdebug')) {
                // try to enable CLI colors
                ini_set('xdebug.cli_color', 1);
                xdebug_print_function_stack();
            } else {
                debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            }
            var_dump(func_get_args());
        } else {
            echo '<div class="textleft clear"><pre>';
            debug_print_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            var_dump(func_get_args());
            echo '</pre></div>';
        }
    }
}

if (!function_exists('app')) {
    /**
     * Alias for call instance of Application
     *
     * @return \Bluz\Application\Application
     */
    function app()
    {
        return \Bluz\Application\Application::getInstance();
    }
}

if (!function_exists('esc')) {
    /**
     * Escape variable for use in View
     *
     * @param string $variable
     * @param int $flags
     * @return string
     */
    function esc($variable, $flags = ENT_HTML5)
    {
        return htmlentities($variable, $flags, "UTF-8");
    }
}

// @codingStandardsIgnoreStart
if (!function_exists('__')) {
    /**
     * translate
     *
     * <code>
     * // simple
     * // equal to gettext('Message')
     * __('Message');
     *
     * // simple replace of one or more argument(s)
     * // equal to sprintf(gettext('Message to %s'), 'Username')
     * __('Message to %s', 'Username');
     * </code>
     *
     * @param $message
     * @return mixed
     */
    function __($message)
    {
        return call_user_func_array(['\Bluz\Translator\Translator', 'translate'], func_get_args());
    }
}

if (!function_exists('_n')) {
    /**
     * translate plural form
     *
     * <code>
     *
     * // plural form + sprintf
     * // equal to sprintf(ngettext('%d comment', '%d comments', 4), 4)
     * _n('%d comment', '%d comments', 4, 4)
     *
     * // plural form + sprintf
     * // equal to sprintf(ngettext('%d comment', '%d comments', 4), 4, 'Topic')
     * _n('%d comment to %s', '%d comments to %s', 4, 'Topic')
     * </code>
     *
     * @param $singular
     * @param $plural
     * @param $number
     * @return mixed
     */
    function _n($singular, $plural, $number)
    {
        return call_user_func_array(['\Bluz\Translator\Translator', 'translatePlural'], func_get_args());
    }
}
// @codingStandardsIgnoreEnd
