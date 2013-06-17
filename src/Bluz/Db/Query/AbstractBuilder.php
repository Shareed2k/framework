<?php
/**
 * Copyright (c) 2013 by Bluz PHP Team
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

/**
 * @namespace
 */
namespace Bluz\Db\Query;

use Bluz\Db\Db;
use Bluz\Db\DbException;
use Bluz\Db\Query\CompositeBuilder;

/**
 * Query Builders classes is responsible to dynamically create SQL queries
 * Based on Doctrine QueryBuilder code
 *
 * @see https://github.com/doctrine/dbal/blob/master/lib/Doctrine/DBAL/Query/QueryBuilder.php
 */
abstract class AbstractBuilder
{
    /**
     * @var Db handler
     */
    protected $db = null;

    /**
     * @var array The array of SQL parts collected
     */
    protected $sqlParts = array(
        'select'  => array(),
        'from'    => array(),
        'join'    => array(),
        'set'     => array(),
        'where'   => null,
        'groupBy' => array(),
        'having'  => null,
        'orderBy' => array()
    );

    /**
     * @var string The complete SQL string for this query
     */
    protected $sql;

    /**
     * @var array The query parameters
     */
    protected $params = array();

    /**
     * @var array The parameter type map of this query
     */
    protected $paramTypes = array();


    /**
     * Known table aliases
     * @var array
     */
    protected $aliases = array();

    /**
     * Initializes a new <tt>AbstractBuilder</tt>
     */
    public function __construct()
    {
        $this->db = app()->getDb();
    }

    /**
     * Execute this query using the bound parameters and their types
     *
     * @return mixed
     */
    public function execute()
    {
        return $this->db->query($this->getSQL(), $this->params, $this->paramTypes);
    }

    /**
     * Return the complete SQL string formed by the current specifications
     *
     * <code>
     *     $sb = new SelectBuilder();
     *     $sb
     *         ->select('u')
     *         ->from('User', 'u');
     *     echo $qb->getSQL(); // SELECT u FROM User u
     * </code>
     *
     * @return string The SQL query string.
     */
    abstract public function getSql();

    /**
     * Return the complete SQL string formed for use
     *
     * <code>
     *     $sb = new SelectBuilder();
     *     $sb
     *         ->select('u')
     *         ->from('User', 'u')
     *         ->where('id = ?', 42);
     *     echo $qb->getQuery(); // SELECT u FROM User u WHERE id = "42"
     * </code>
     *
     * @return string
     */
    public function getQuery()
    {
        $sql = $this->getSql();

        $sql = str_replace('%', '%%', $sql);
        $sql = str_replace('?', '"%s"', $sql);

        // replace mask by data
        return vsprintf($sql, $this->getParameters());
    }

    /**
     * Sets a query parameter for the query being constructed
     *
     * <code>
     *     $sb = new SelectBuilder();
     *     $sb
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.id = :user_id')
     *         ->setParameter(':user_id', 1);
     * </code>
     *
     * @param string|integer $key The parameter position or name
     * @param mixed $value The parameter value
     * @param integer $type PDO::PARAM_*
     * @return self instance
     */
    public function setParameter($key, $value, $type = \PDO::PARAM_STR)
    {
        if (null == $value) {
            return $this;
        }

        if (null == $key) {
            $key = sizeof($this->params);
        }

        $this->params[$key] = $value;
        $this->paramTypes[$key] = $type;

        return $this;
    }

    /**
     * Sets a collection of query parameters for the query being constructed
     *
     * <code>
     *     $sb = new SelectBuilder();
     *     $sb
     *         ->select('u')
     *         ->from('users', 'u')
     *         ->where('u.id = :user_id1 OR u.id = :user_id2')
     *         ->setParameters(array(
     *             ':user_id1' => 1,
     *             ':user_id2' => 2
     *         ));
     * </code>
     *
     * @param array $params The query parameters to set
     * @param array $types  The query parameters types to set
     * @return self instance
     */
    public function setParameters(array $params, array $types = array())
    {
        $this->paramTypes = $types;
        $this->params = $params;

        return $this;
    }

    /**
     * Gets a (previously set) query parameter of the query being constructed
     *
     * @param mixed $key The key (index or name) of the bound parameter
     * @return mixed The value of the bound parameter.
     */
    public function getParameter($key)
    {
        return isset($this->params[$key]) ? $this->params[$key] : null;
    }

    /**
     * Gets all defined query parameters for the query being constructed
     *
     * @return array The currently defined query parameters
     */
    public function getParameters()
    {
        return $this->params;
    }

    /**
     * Either appends to or replaces a single, generic query part
     *
     * The available parts are: 'select', 'from', 'set', 'where',
     * 'groupBy', 'having' and 'orderBy'
     *
     * @param string  $sqlPartName
     * @param string  $sqlPart
     * @param boolean $append
     * @return self instance
     */
    protected function addQueryPart($sqlPartName, $sqlPart, $append = false)
    {
        $isArray = is_array($sqlPart);
        $isMultiple = is_array($this->sqlParts[$sqlPartName]);

        if ($isMultiple && !$isArray) {
            $sqlPart = array($sqlPart);
        }

        if ($append) {
            if ($sqlPartName == "orderBy" || $sqlPartName == "groupBy"
                || $sqlPartName == "select" || $sqlPartName == "set") {
                foreach ($sqlPart as $part) {
                    $this->sqlParts[$sqlPartName][] = $part;
                }
            } elseif ($isArray && is_array($sqlPart[key($sqlPart)])) {
                $key = key($sqlPart);
                $this->sqlParts[$sqlPartName][$key][] = $sqlPart[$key];
            } elseif ($isMultiple) {
                $this->sqlParts[$sqlPartName][] = $sqlPart;
            } else {
                $this->sqlParts[$sqlPartName] = $sqlPart;
            }

            return $this;
        }

        $this->sqlParts[$sqlPartName] = $sqlPart;

        return $this;
    }

    /**
     * Get a query part by its name
     *
     * @param string $queryPartName
     * @return mixed $queryPart
     */
    protected function getQueryPart($queryPartName)
    {
        return $this->sqlParts[$queryPartName];
    }

    /**
     * Reset single SQL part
     *
     * @param string $queryPartName
     * @return self instance
     */
    protected function resetQueryPart($queryPartName)
    {
        $this->sqlParts[$queryPartName] = is_array($this->sqlParts[$queryPartName])
            ? array() : null;

        return $this;
    }

    /**
     * prepareCondition
     *
     * @param array $args
     * @internal param $condition
     * @return string
     */
    protected function prepareCondition($args = array())
    {
        $condition = array_shift($args);
        foreach ($args as &$value) {
            if (is_array($value)) {
                foreach ($value as &$element) {
                    $element = $this->db->quote($element);
                }
                $value = join(',', $value);
            } else {
                $value = $this->db->quote($value);
            }
            $condition = preg_replace('/\?/', $value, $condition, 1);
        }

        return $condition;
    }

    /**
     * Gets a string representation of this QueryBuilder which corresponds to
     * the final SQL query being constructed.
     *
     * @return string The string representation of this QueryBuilder.
     */
    public function __toString()
    {
        return $this->getSQL();
    }
}
