<?php

namespace Milanmadar\CoolioORM;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Types\Type;
use Milanmadar\CoolioORM\Geo\DoctrineDBALType\GeometryType;
use Milanmadar\CoolioORM\Geo\DoctrineDBALType\TopoGeometryType;
use Milanmadar\CoolioORM\Geo\DoctrineDBALType\TextArrayType;
use Milanmadar\CoolioORM\Geo\DoctrineDBALType\TextArrayBracketsType;

class ORM
{
    /** @var array<Connection> */
    private array $doctrineConnectionsByUrl;

    /** @var array<StatementRepository> */
    private array $statementRepositories;

    /** @var array<string, Manager> */
    private array $entityManagers;

    /** @var EntityRepository */
    private EntityRepository $entityRepository;

    // Singleton
    private static ?ORM $instance;

    private static bool $staticTypeAdded = false;
    private static bool $staticTypeMapped = false;

    /**
     * Singleton, using the same as Symfony service container
     * @return ORM
     */
    public static function instance(): ORM
    {
        if(!isset(self::$instance)) {
            self::$instance = new ORM();
        }
        return self::$instance;
    }

    /**
     * Used for tests
     * @internal
     */
    public static function _clearSingleton(): void
    {
        self::$instance = null;
    }

    /**
     * constructor
     */
    public function __construct()
    {
        if(!self::$staticTypeAdded) {
            Type::addType('geometry', GeometryType::class);
            Type::addType('topogeometry', TopoGeometryType::class);
            Type::addType('_text', TextArrayType::class);
            Type::addType('text[]', TextArrayBracketsType::class);
            self::$staticTypeAdded = true;
        }

        $this->doctrineConnectionsByUrl = [];
        $this->statementRepositories = [];
        $this->entityManagers = [];
    }

    /**
     * Db connection, with a connection Url, <b>SINGLETON</b>
     * @param string $connUrl Usually list $_ENV['DB_CONNECTION_URL']
     * @return Connection
     */
    public function getDbByUrl(string $connUrl): Connection
    {
        if(empty($connUrl)) {
            throw new \InvalidArgumentException('ORM::getDbByUrl() Empty connection url. Maybe check your Manager->getDefaultDbTable() and your .env files (or environment variables)');
        }
        if(!isset($this->doctrineConnectionsByUrl[$connUrl])) {
            $connectionParams = (new DsnParser())->parse($connUrl);
            $this->doctrineConnectionsByUrl[$connUrl] = DriverManager::getConnection($connectionParams);
            if(!self::$staticTypeMapped && str_contains($connUrl, 'pgsql')) {
                $this->doctrineConnectionsByUrl[$connUrl]->getDatabasePlatform()->registerDoctrineTypeMapping('geometry', GeometryType::NAME);
                $this->doctrineConnectionsByUrl[$connUrl]->getDatabasePlatform()->registerDoctrineTypeMapping('topogeometry', TopoGeometryType::NAME);
                $this->doctrineConnectionsByUrl[$connUrl]->getDatabasePlatform()->registerDoctrineTypeMapping('_text', TextArrayType::NAME);
                $this->doctrineConnectionsByUrl[$connUrl]->getDatabasePlatform()->registerDoctrineTypeMapping('text[]', TextArrayBracketsType::NAME);
                //$this->doctrineConnectionsByUrl[$connUrl]->getDatabasePlatform()->registerDoctrineTypeMapping('geometry', 'string');
                self::$staticTypeMapped = true;
            }
//            /** @var \PDO $pdoConn */
//            $pdoConn = $this->dbsByConnUrl[$connUrl]->getWrappedConnection()->getWrappedConnection(); @ php stan-ig nore-li ne
////            $pdoConn->setAttribute(\PDO::ATTR_PERSISTENT, true);
//            $pdoConn->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
        }
        return $this->doctrineConnectionsByUrl[$connUrl];
    }

    /**
     * Entity Manager, <b>SINGLETON</b>
     * @template T of \Milanmadar\CoolioORM\Manager
     * @param T $mgrClassName
     * @param Connection|null $db Optional. Default is the class default: $mgrClassName::getDbSelector()
     * @return T
     *
     * @phpstan-param class-string<T> $mgrClassName
     * @phpstan-return T
     */
    public function entityManager(string $mgrClassName, ?Connection $db = null): Manager
    {
        if(!isset($db)) {
            $db = $this->getDbByUrl( $mgrClassName::getDbDefaultConnectionUrl() );
        }

        $k = $mgrClassName .Utils::getDbConnUrl($db);

        if(!isset($this->entityManagers[$k])) {
            $this->entityManagers[$k] = new $mgrClassName( $this, $db, $this->getEntityRepository() );
        }

        /** @var T of Milanmadar\CoolioORM\Manager */
        return $this->entityManagers[$k];
    }

    /**
     * Creates a new QueryBuilder that will use the given database connection, <b>NEW EVERY TIME</b>
     * @param Connection $db
     * @return QueryBuilder
     */
    public function createQueryBuilderByConnection(Connection $db): QueryBuilder
    {
        return new QueryBuilder($this, $db);
    }

    /**
     * Creates a new QueryBuilder that will use the given connection url, <b>NEW EVERY TIME</b>
     * @param string $connUrl Usually $_ENV['DB_...']
     * @return QueryBuilder
     */
    public function createQueryBuilderByConnectionUrl(string $connUrl): QueryBuilder
    {
        return new QueryBuilder($this, $this->getDbByUrl($connUrl) );
    }

    /**
     * MySQL Prepared Statements EntityRepository, <b>SINGLETON</b>
     * @param Connection $db
     * @return StatementRepository
     */
    public function getStatementRepositoryByConnection(Connection $db): StatementRepository
    {
        $key = Utils::getDbConnUrl($db) .'/' .spl_object_id($db);
        if(!isset($this->statementRepositories[$key])) {
            $this->statementRepositories[$key] = new StatementRepository( $db );
        }
        return $this->statementRepositories[$key];
    }

    /**
     * Singleton Entity Reposity to do less Database reads, coz it stores the Entities
     * @return EntityRepository
     */
    public function getEntityRepository(): EntityRepository
    {
        if(!isset($this->entityRepository)) {
            $this->entityRepository = new EntityRepository();
        }
        return $this->entityRepository;
    }

    /**
     * @param Entity $entity
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function save(Entity $entity): void
    {
        $class = get_class($entity);
        /*$parts = explode('\\', $class);
        array_pop($parts);
        $class = implode('\\', $parts).'\\Manager';*/
        $class = substr($class, 0, -6).'Manager';
        $this->entityManager($class)->save($entity); /* @phpstan-ignore-line */
    }

    /**
     * @param array<Entity> $entities
     * @return void
     * @throws \Doctrine\DBAL\Exception
     */
    public function bulkInsert(array $entities): void
    {
        $separateEntities = [];
        foreach($entities as $entity) {
            $class = get_class($entity);
            $separateEntities[$class][] = $entity;
        }

        foreach($separateEntities as $class => $entities) {
            $class = substr($class, 0, -6).'Manager';
            $this->entityManager($class)->bulkInsert($entities); /* @phpstan-ignore-line */
        }
    }

}