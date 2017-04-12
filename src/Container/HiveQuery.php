<?php
/**
 * This file is part of the subcosm.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

namespace Subcosm\Hive\Container;


/**
 * Class HiveQuery
 * @package Subcosm\Hive\Container
 */
class HiveQuery
{
    /**
     * The full query string.
     *
     * @var string
     */
    public $fullQuery;

    /**
     * The full query without root descriptor.
     *
     * @var string
     */
    public $rootlessQuery;

    /**
     * The query without the first token of the full query.
     *
     * @var string|null
     */
    public $query;

    /**
     * The first token of the full query.
     *
     * @var string
     */
    public $firstToken;

    /**
     * The last token of the full query.
     *
     * @var string
     */
    public $lastToken;

    /**
     * The query without the first and the last token of the full query.
     *
     * @var string|null
     */
    public $segmentedQuery;

    /**
     * The token count of the full query.
     *
     * @var int
     */
    public $tokenCount = 1;

    /**
     * Defines if the query calls for the root node or not.
     *
     * @var bool
     */
    public $callsRoot = false;

    /**
     * HiveQuery constructor.
     * @param string $normalizedQuery
     * @param string $divider
     * @param string $rootDescriptor
     */
    public function __construct(string $normalizedQuery, string $divider, string $rootDescriptor)
    {
        $this->parse($this->fullQuery = $normalizedQuery, $divider, $rootDescriptor);
    }

    /**
     * detects whether the query was empty or not.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->fullQuery);
    }

    /**
     * query parser
     *
     * @param string $query
     * @param string $divider
     * @param string $rootDescriptor
     */
    protected function parse(string $query, string $divider, string $rootDescriptor)
    {
        $this->rootlessQuery = $query;

        if ( 0 === strpos($query, $rootDescriptor) ) {
            $this->rootlessQuery = $query = substr($query, strlen($rootDescriptor));
            $this->callsRoot = true;
        }

        $tokens = explode($divider, $query);

        $this->tokenCount = count($tokens);

        if ( count($tokens) === 1 ) {
            $this->firstToken = $this->lastToken = $query;
        }

        if ( count($tokens) === 2 ) {
            list($this->firstToken, $this->lastToken) = $tokens;
            $this->query = $this->lastToken;
        }

        if ( count($tokens) > 2 ) {
            $this->firstToken = array_shift($tokens);
            $this->lastToken = array_pop($tokens);
            $this->segmentedQuery = join($divider, $tokens);
            $this->query = $this->segmentedQuery.$divider.$this->lastToken;
        }
    }
}
