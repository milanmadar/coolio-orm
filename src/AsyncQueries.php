<?php

namespace Milanmadar\CoolioORM;

class AsyncQueries
{
    /** @var array<string, array{conn_url:string, sql:string, params:array<int<0, max>|string, mixed>}> */
    private array $queries;
    private int $maxWaitForResults;

    /**
     * Constructor
     * @param int $maxWaitForResults Maximum time to wait for results in seconds (default is 3600 seconds = 1 hour)
     */
    public function __construct(int $maxWaitForResults = 3600)
    {
        $this->maxWaitForResults = $maxWaitForResults;
        $this->queries = [];
    }

    public function addQuery_fromQueryBuilder(string $name, QueryBuilder $queryBuilder): self
    {
        $connParams = $queryBuilder->getDoctrineConnection()->getParams();

        if (($connParams['driver'] ?? '') !== 'pdo_pgsql') {
            throw new \InvalidArgumentException('Only PostgreSQL queries are supported');
        }

        foreach (['user', 'password', 'host', 'port'] as $param) {
            if (empty($connParams[$param])) {
                throw new \InvalidArgumentException("Missing connection parameter: {$param}");
            }
        }

        // Use the more robust key=value connection string format
        $connUrl = sprintf(
            'host=%s port=%d dbname=%s user=%s password=%s',
            $connParams['host'], // @phpstan-ignore-line We check this above but phpstan doesn't know
            $connParams['port'], // @phpstan-ignore-line We check this above but phpstan doesn't know
            $connParams['dbname'], // @phpstan-ignore-line We check this above but phpstan doesn't know
            $connParams['user'], // @phpstan-ignore-line We check this above but phpstan doesn't know
            $connParams['password'] // @phpstan-ignore-line We check this above but phpstan doesn't know
        );
        /*$connUrl = sprintf(
            'postgresql://%s:%s@%s:%d%s',
            $connParams['user'],
            $connParams['password'],
            $connParams['host'],
            $connParams['port'],
            !empty($connParams['dbname']) ? '/' . $connParams['dbname'] : ''
        );*/

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
            // Append application_name to the connection string
            $connString = $query['conn_url'] . ' application_name=conn' . $i;

            // connect
            $conn = @pg_connect($connString, PGSQL_CONNECT_FORCE_NEW);
            if ($conn === false) {
                $this->closeConnections($connections);
                throw new \RuntimeException("Milanmadar\CoolioORM\AsyncQueries::fetch() Could not connect to PostgreSQL database with URL: " . $connString);
            }

            // 100% sure its not the same
//            if (pg_dbname($conn) !== $this->queries[$name]['conn_params']['dbname']) { // You'll need to store dbname in addQuery
//                // throw new \RuntimeException("Connected to wrong DB!");
//            }

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
                    @pg_close($conn);
                    $this->closeConnections($connections);
                    throw new \RuntimeException("Milanmadar\CoolioORM\AsyncQueries::fetch() Could not escape value for parameter '$param': " . pg_last_error($conn));
                }

                //$sql = str_replace(':' . $param, $escapedValue, $sql);
                /** @var string $sql */
                $sql = preg_replace('/:' . preg_quote((string)$param, '/') . '\b/', $escapedValue, $sql);
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
                $this->closeConnections($connections);
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
            if (stream_select($read, $write, $except, $this->maxWaitForResults)) { /* @phpstan-ignore-line */
                foreach ($connections as $name => $connData) {
                    if (in_array($connData['sock'], $read, true))
                    {
                        $res = pg_get_result($connData['conn']);
                        if ($res !== false) {
                            $results[$name] = pg_fetch_all($res);
                        }

                        @pg_close($connData['conn']);

                        // remove this connection since it's done
                        unset($connections[$name]);
                    }
                }
            }
            else {
                $this->closeConnections($connections);
                throw new \RuntimeException("Milanmadar\CoolioORM\AsyncQueries::fetch() stream_select() timed out or encountered an error for this query");
            }
        }

        return new AsyncResultset($results);
    }

    /**
     * Close all given connections.
     * @param array<string, array{conn:\PgSql\Connection}> $connections
     * @return void
     */
    private function closeConnections(array &$connections): void
    {
        foreach ($connections as $connData) {
            if (!empty($connData['conn'])) { // @phpstan-ignore-line
                @pg_close($connData['conn']);
            }
        }
        $connections = [];
    }

}