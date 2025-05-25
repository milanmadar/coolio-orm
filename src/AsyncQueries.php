<?php

namespace Milanmadar\CoolioORM;

use Milanmadar\CoolioORM\Geo\AsyncResultset;

class AsyncQueries
{
    /** @var array<string, array{conn_url:string, sql:string, params:array<int<0, max>|string, mixed>}> */
    private array $queries;

    public function __construct()
    {
        $this->queries = [];
    }

    public function addQuery_fromQueryBuilder(string $name, QueryBuilder $queryBuilder): self
    {
        $connParams = $queryBuilder->getDoctrineConnection()->getParams();
        if(!isset($connParams['driver']) || $connParams['driver'] !== 'pdo_pgsql') {
            throw new \InvalidArgumentException('Milanmadar\CoolioORM\AsyncQueries: Only PostgreSQL queries are supported');
        }
        if(!isset($connParams['user']) || !isset($connParams['password']) || !isset($connParams['host']) || !isset($connParams['port'])) {
            throw new \InvalidArgumentException('Milanmadar\CoolioORM\AsyncQueries: Missing connection parameters for PostgreSQL');
        }

        $connUrl ='postgresql://' . $connParams['user'] . ':' . $connParams['password'] . '@' . $connParams['host'] . ':' . $connParams['port'];
        if(!empty($connParams['dbname'])) $connUrl .= '/' . $connParams['dbname'];

        return $this->addQuery(
            $name,
            $connUrl,
            $queryBuilder->getSQL(),
            $queryBuilder->getParameters()
        );
    }

    /**
     * @param string $name
     * @param string $connUrl
     * @param string $sql
     * @param array<int<0, max>|string, mixed> $params
     * @return $this
     */
    public function addQuery(string $name, string $connUrl, string $sql, array $params = []): self
    {
        $this->queries[$name] = [
            'conn_url' => $connUrl,
            'sql' => $sql,
            'params' => $params,
        ];
        return $this;
    }

    /**
     * Runs all queries in parallel and returns all the results at once
     * @return AsyncResultset
     */
    public function fetch(): AsyncResultset
    {
        /** @var array<string, array{conn:\PgSql\Connection, sql:string, sock:resource}> $connections */
        $connections = [];

        /** @var array<string, array<string, mixed>> $results */
        $results = [];

        // create a separate connection for each query
        $i = 1;
        foreach ($this->queries as $name => $query)
        {
            // connect
            $connUrl = $query['conn_url']. '?application_name=conn'.$i;
            $conn = pg_connect($connUrl);
            if ($conn === false) {
                throw new \RuntimeException("Milanmadar\CoolioORM\AsyncQueries::fetch() Could not connect to PostgreSQL database with URL: " . $connUrl);
            }

            // replace the parameters in the SQL query
            uksort($query['params'], function($a, $b) {
                return strlen((string)$b) <=> strlen((string)$a);
            });
            $sql = $query['sql'];
            foreach ($query['params'] as $param => $value)
            {
                if (is_array($value)) {
                    $value = implode(',', $value);
                }

                $escapedValue = pg_escape_literal($conn, $value);
                if($escapedValue === false) {
                    pg_close($conn);
                    foreach($connections as $c) {
                        pg_close($c['conn']);
                    }
                    throw new \RuntimeException("Milanmadar\CoolioORM\AsyncQueries::fetch() Could not escape value for parameter '$param': " . pg_last_error($conn));
                }

                $sql = str_replace(':' . $param, $escapedValue, $sql);
            }

            // this is to keep track on the connections
            $connections[$name] = [
                'conn' => $conn,
                'sql' => $sql, // for debug
            ];

            ++$i;
        }

        // send the queries through each connection
        foreach($connections as $name => $connData)
        {
            $conn = $connData['conn'];
            $sql = $connData['sql'];

            // send the query
            if (!pg_send_query($conn, $sql)) {
                pg_close($conn);
                unset($connections[$name]);
                foreach($connections as $c) {
                    pg_close($c['conn']);
                }
                throw new \RuntimeException("Milanmadar\CoolioORM\AsyncQueries::fetch() Could not send query for '$name': " . pg_last_error($conn));
            }

            // we will need this to read the results later
            $connections[$name]['sock'] = pg_socket($conn);
        }

        // read the results
        while (count($connections) > 0)
        {
            // Extract the sockets for stream_select
            $read = array_map(fn($c) => $c['sock'], $connections);
            $write = null;
            $except = null;

            // see which sockets are ready
            if (stream_select($read, $write, $except, 4)) { /* @phpstan-ignore-line */
                foreach ($connections as $name => $connData) {
                    if (in_array($connData['sock'], $read, true))
                    {
                        $res = pg_get_result($connData['conn']);
                        if ($res !== false) {
                            $results[$name] = pg_fetch_all($res);
                        }

                        pg_close($connData['conn']);

                        // remove this connection since it's done
                        unset($connections[$name]);
                    }
                }
            }
            else {
                foreach ($connections as $name => $connData) {
                    unset($connections[$name]);
                }
                throw new \RuntimeException("Milanmadar\CoolioORM\AsyncQueries::fetch() stream_select() timed out or encountered an error for this query");
            }
        }

        return new AsyncResultset($results);
    }

}