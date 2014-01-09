<?php
/**
 * @copyright Bluz PHP Team
 * @link https://github.com/bluzphp/framework
 */

/**
 * @namespace
 */
namespace Bluz\Grid\Helper;

use Bluz\Application\Application;
use Bluz\Grid;

return
    /**
     * @return string
     */
    function () {
    /**
     * @var Grid\Grid $this
     */
    return $this->getUrl(['page' => $this->pages()]);
    };
