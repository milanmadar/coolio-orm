# PHP Database Acces (ORM, DBAL)

ORM (Object Relation Mapper) is a set of classes that represent your data in PHP code and help you with reading/writing data from/to the db (see the Scaffold section to generate your Model from a database table).

This ORM is well tested on MySQL and PostgreSQL (also with PostGIS extension for Geometry and Geography). It should work on any other SQL database that Doctrine DBAL supports, but we didn't test it on the others.

CoolioORM is a database-first approach, which means you first create your database tables, and then you generate (scaffold) the PHP classes from the database tables.

It integrate well into <a href="https://symfony.com/doc/current/index.html" target="_blank">Symfony framework</a> as a Bundle (you can autowire `\Milanmadar\CoolioORM\ORM`).

**NOTE: This ORM was designed for medium to large projects, where you have thousands or millions of rows in the database tables. Hence, It doesn't handle many-to-one or many-to-many relations like `$catagory->getProducts()` (because `getProducts()` would return 100.000 rows). But its very easy to do that with the query builder, and you have full control (and easy control) over `LIMIT` and `ORDER BY` there.**

It is based on <a href="https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/introduction.html#introduction" target="_blank">Doctrine DBAL</a>.

The basic goodies are:

- Create Foreign Keys in your database tables and CoolioORM will automatically create the relations in the PHP classes
- You can switch between databases if you want (like copy something from production to your local dev database, or do a migration from one db to another, with data processing)
- It supports everything that Doctrine DBAL supports, additionally it supports Arrays easier and NULLs easier (with `$queryBuilder->andWhereColumn()`)
- Supports PostGIS Geometries (2D and 3D)
- Caches your query results to save database trips when possible (you can control that part too) 
- Easy to get started with, and as your project grows complexity (multiple databases, large datasets, crazy relations) you can use the more advanced features that are still easy to use
- Thread safe: having multiple threads, and doing `$orm = new \Milanmadar\CoolioORM\ORM()` in each thread is safe, because each instance will have its own database connection, cache, prepared statements, etc

---

## Installation

```bash
composer require milanmadar/coolio-orm
```

You need the following environmental variables (like `.env`, also known as `DotEnv`):

```dotenv
# The default database connection
DB_DEFAULT=pdo-mysql://my_user:my_password@localhost/my_database
# See here about connection urls: 
# https://www.doctrine-project.org/projects/doctrine-dbal/en/4.2/reference/configuration.html#connecting-using-a-url
#
# You can define more database connections, just start with DB_ and then the name of the connection
# DB_OTHER=pdo-pgsql://other_user:other_password@localhost_or_otherhost/my_other_database

# Max how many entities should we keep in cache
COOLIO_ORM_ENTITY_REPO_MAX_ITEMS=1000

# How many times should we retry a failed query
COOLIO_ORM_RETRY_ATTEMPTS=1

# How long should we wait between retrying a failed query
COOLIO_ORM_RETRY_SLEEP=2

# PostGIS: The default SRID (scaffolding from the database will use what is in the db, not this default)  
GEO_DEFAULT_SRID=4326
```

---

## Usage with PostGIS Geometries

We will create a demo table that hold certain types of 2D geometries, like Point, LineString, Polygon, etc (see `src/Geo/Shape2D` folder). But the ORM support 3D geometries too, like PointZ, LineStringZ, PolygonZ, etc (see `src/Geo/ShapeZ` folder).

### Step 1: Create database table

```sql
-- PostGIS Example
CREATE TABLE geometry_test (
   id SERIAL PRIMARY KEY,
   title VARCHAR(255) NOT NULL,
   difficulty INT NOT NULL DEFAULT 0,

   -- geometry types
   point_geom             geometry(Point, 4326),
   linestring_geom        geometry(LineString, 4326),
   polygon_geom           geometry(Polygon, 4326),
   multipoint_geom        geometry(MultiPoint, 4326),
   multilinestring_geom   geometry(MultiLineString, 4326),
   multipolygon_geom      geometry(MultiPolygon, 4326),
   geomcollection_geom    geometry(GeometryCollection, 4326),

   -- curved geometry types
   circularstring_geom    geometry(CIRCULARSTRING, 4326),
   compoundcurve_geom     geometry(COMPOUNDCURVE, 4326),
   curvedpolygon_geom     geometry(CURVEPOLYGON, 4326),
   multicurve_geom        geometry(MULTICURVE, 4326)
);
```

### Step 2: Scaffold

Scaffold means generating the PHP classes (Entity and Manager) from the Database table. In you terminal, run:

```bash
vendor/bin/console coolio-orm:scaffold
```

It will ask you which table you want to scaffold, and ask questions if needed.

### Step 3: Use it in your PHP code

In this example we scaffolded `GeometryTest`. We will get 2 classes:

- `GeometryTest\Entity` holds 1 row from the `geometry_test` table, each column (field) has setters getters
- `GeometryTest\Manager` to read and write data from/to the `geometry_test` table

```php
use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\Geo\Shape2D;
use Milanmadar\CoolioORM\Geo\GeoFunctions;
use App\Model\GeometryTest;

$orm = ORM::instance(); // In Symfony, you can autowire `\Milanmadar\CoolioORM\ORM`

$geotestManager = $orm->entityManager( GeometryTest\Manager::class );

//
// Create a new entity (representing a table row), and fill it with data
//
$geotest = $geotestManager
    ->createEntity()
    ->setTitle("My first Geometry enabled Entity")
    ->setDifficulty( 1 )
    ->setPointGeom( new Shape2D\Point(1, 2) )
    ->setLinestringGeom( new LineString([ new Point(1, 1), new Point(2, 2), new Point(3, 3), new Point(4, 4) ], 4326) );

// Save it to the database
$geotestManager->save($geotest);

// Now our Entity has an ID
$geotest->getId(); // 1

//
// Read entities (rows) from the database
//

// Get an entity from the database
$geotest = $geotestManager->findById( 1 );
$geotest->getTitle(); // "My first Geometry enabled Entity"
$geotest->getPointGeom()->getX(); // 1

// Get many entities from the database
$geotests = $geotestManager->findManyWhere("difficult = :Safe_Difficulty_Param", ['Safe_Difficulty_Param'=>1]);
foreach($geotests as $geotest) {
    echo $geotest->getTitle()."\n";
}

//
// QueryBuilder: SELECT
//
$geotests = $geotestManager
    ->createQueryBuilder()
    ->select('title', 'difficulty', 'linestring_geom') // if you want select(*) then you can omit this line
    ->andWhereColumn('difficulty', '=', 1)
    ->andWhereColumn('linestring_geom', '!=', null)
    ->orderBy('difficulty', 'asc')
    ->limit(0, 10)
    ->groupBy('difficulty')
    ->fetchManyEntity() // fetches many entities
;
/* 
There are many fetches, like:
     ->fetchAllAssociative() // fetches all rows as associative array
     ->fetchOneEntity() // fetches 1 entity
You will find them all in the below documentation
*/

//
// Use PostGIS ST_* functions
//
$ST_Distance_expression = GeoFunctions::ST_DWithin(
    'point_geom',
    new LineString([new Point(1, 1, 1), new Point(2, 1, 1), new Point(2, 2, 1), new Point(1, 1, 1)]),
    5
);

$isThatRowWithinDistance = $mgr->createQueryBuilder()
    ->select($ST_Distance_expression)
    ->andWhereColumn('id', '=', 1)
    ->fetchOne();
// $isThatRowWithinDistance is TRUE or FALSE here        

$entitiesWithinDistance = $mgr->createQueryBuilder()
    ->andWhere($ST_Distance_expression)
    ->fetchManyEntity();
foreach($entitiesWithinDistance as $geotestEntity) {
    echo $geotestEntity->getTitle()."\n";
}

//
// Delete the entity
//
$geotestManager->delete($geotest);
```

There are more supported PostGIS geometry types, look into the `src/Geo/Shape2D` and `src/Geo/ShapeZ` (3D) folder.

## Usage for classic tables

We will have Shops, and each Shop can have many Products.

### Step 1: Create database table

```sql
-- MySQL Example
CREATE TABLE `shop` (
    `id` int NOT NULL AUTO_INCREMENT,
    `name` varchar(128),
    PRIMARY KEY (`id`)
);

CREATE TABLE `product` (
    `id` int NOT NULL AUTO_INCREMENT,
    `shop_id` int NOT NULL,
    `title` varchar(128),
    PRIMARY KEY (`id`),
    KEY `fk_shop_id` (`shop_id`),
    CONSTRAINT `fk_shop_id` FOREIGN KEY (`shop_id`) REFERENCES `shop` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
);
```

### Step 2: Scaffold

Scaffold means generating the PHP classes (Entity and Manager) from the Database table. In you terminal, run the following command twice, to first generate the Shop, then second time generate the Product:

```bash
vendor/bin/console coolio-orm:scaffold
```

It will ask you which table you want to scaffold (at first run the `shop`, at second run the `product`), and ask questions if needed.

### Step 3: Use it in your PHP code

In this example we scaffolded `Shop` and `Product` classes. For each of them we will get 2 classes:

- `Shop\Entity` holds 1 row from the `shop` table, each column (field) has setters getters
- `Shop\Manager` to read and write data from/to the `shop` table
- `Product\Entity` holds 1 row from the `product` table, each column (field) has setters getters
- `Product\Manager` to read and write data from/to the `product` table

```php
use Milanmadar\CoolioORM\ORM;
use App\Model\Shop;
use App\Model\Products;

$orm = ORM::instance(); // In Symfony, you can autowire `\Milanmadar\CoolioORM\ORM`

$shopManager = $orm->entityManager( Shop\Manager::class );
$productManager = $orm->entityManager( Product\Manager::class );

//
// Create a Shop with some Products
//

// Create a new Shop (representing a table row), and fill it with data
$shop = $shopManager
    ->createEntity()
    ->setName('Coolio Shop');
// Save it to the database
$shopManager->save($shop);
    
// Create some products
$products = [];
// One product
$products[] = $productManager
    ->createEntity()
    ->setTitle('Coolio T-shirt')
    ->setShop( $shop ); // Set the shop for the product
// Another product
$products[] = $productManager
    ->createEntity()
    ->setTitle('Coolio Mug')
    ->setShop( $shop ); // Set the shop for the product

// Save each product
foreach($products as $product) {
   $productManager->save($product);
}

// Now all our entity has an IDs
$shop->getId(); // 1
$products[0]->getId(); // 1
$products[1]->getId(); // 2

//
// Read all the products from the shop
// Imagine there are 10.000 products in the shop
//

$shop = $shopManager->findById( 1 );

// Read all Products with the "paging" method, with chunks of 200s
// (Paging means we will increase the LIMIT offset by 200 each time)
$lastOffset = 0;
do {
    $products = $productManager
        ->createQueryBuilder()
        ->andWhereColumn('shop_id', '=', $shop->getId())
        ->orderBy('id', 'asc')
        ->limit($lastOffset, 200) // chunks of 200
        ->fetchManyEntity(); // this gives us an array of Product\Entity objects
    foreach($products as $product) {
        echo $product->getTitle()."\n";
    }
    $lastOffset += 200;
} 
while( !empty($products) );

// Read all Products with the "cursor" method, with chunks of 200s
// (Cursoring means we will read the Products ordered by their ID, always remembering the last ID we read)
// The cursoring is better for large datasets (the database needs to work less)
$lastProductId = 0;
do {
    $products = $productManager
        ->createQueryBuilder()
        ->andWhereColumn('shop_id', '=', $shop->getId())
        ->andWhereColumn('id', '>', $lastProductId)  // from the last ID we already read
        ->orderBy('id', 'asc')
        ->limit(0, 200) // chunks of 200
        ->fetchManyEntity(); // this gives us an array of Product\Entity objects
    foreach($products as $product) {
        $lastProductId = $product->getId(); // remember the last ID we read
        echo $product->getTitle()."\n";
    }
} 
while( !empty($products) );


//
// Delete the shop
//
$shopManager->delete($shop);
// The deletion of the Products happens in the database because of the FOREIGN KEY...ON DELETE CASCADE
```

**Enjoy!**

---

## Entity, Manager (ORM)

A Model means 2 classes:

- An `CoolioORM\Entity` class holds data from a single row from a db table. This has the accessors (setters/getters).  
- An `CoolioORM\Manager` class handles the db operations (internally it uses [Doctrine DBAL](README.md#database-connections)). This has save(), delete(), findById(), and some other methods built in.

The `CoolioORM\ORM` class can create the Managers with the `$orm->entityManager( MyManager::class )` method (it also handles database connections and several other things). So if you have dependency injection (like Symfony autowire) then the `Milanmadar\CoolioORM\ORM` class is the one you want to inject (autowire) into your Controllers and other classes that needs database access (like Commands in Symfony).

### The Entity

The Entity holds the data of a single row from a db table. It has setters/getters, their names match the fields in the db table:  
`$entity->getId()`, `$entity->getTitle()`, `$entity->setTitle("Easy")`, ...

You can get all the data from an Entity as an associative array:  
`$data = $entity->_getData(); echo $data['title'];`.

You can use PHP's `clone` keyword to copy an Entity, except it's ID (because that should be unique):  
`$copyEntity = clone $entity;`

The Entities have fluent setters, meaning you can write them like this:  
`$entity->setTitle("title")->setPrice(100, 'USD')->setSomething("something");`

The Entity has the `Milanmadar\CoolioORM\ORM` internally, so you can use other Managers in it to create special relations:

```php
#
# EXAMPLE 1: Get the thumbnail picture of a Product
#
namespace App\Model\Product;

use App\Model\Picture;
use Milanmadar\CoolioORM;

class Entity extends CoolioORM\Entity
{
    public function getThumbnail(): Picture\Entity
    {
        return $this->orm->entityManager( Picture\Manager::class )
            ->createQueryBuilder()
            ->andWhereColumn('product_id', '=', $this->getId())
            ->andWhereColumn('type', '=', 'thumbnail')
            ->orderBy('position', 'asc')
            ->limit(0, 1)
            ->fetchOneEntity();
    }
}

#
# EXAMPLE 1: Get the certain special Products of a Shop
#
namespace App\Model\Shop;

use App\Model\Product;
use Milanmadar\CoolioORM;

class Entity extends CoolioORM\Entity
{
    public function getCheapestProduct(): Product\Entity 
    {
        return $this->orm->entityManager( Product\Manager::class )
            // in real life you would probably use the QueryBuilder
            ->findOneWhere("shop_id=? ORDER BY price ASC LIMIT 1", [$this->getId()]);
    }
    
    public function getAllProducts(: array
    {
        return $this->orm->entityManager( Product\Manager::class )
            // in real life you would probably use the QueryBuilder
            ->findManyWhere("shop_id=? ORDER BY position ASC", [$this->getId()]);
    }
}
```

#### Entity Relations

For example, there are Catalogs, and there are Items in the Catalogs. In the db you would have these:
```php
# catalogs table:
catalogs.id
catalogs.title

# items table:
items.id
items.description
items.catalog_id # Foreign Key to catalogs.id (ON DELETE SET NULL, ON UPDATE CASCADE)
```

When you setup the foreign keys correctly in the db table scheme, the [Scaffold](README.md#scaffold) will give you Entities as such (simplified code without namespace just for explanation):
```php
class Catalog
{
  public function getId(): int {...}
  public function setId( int $id ) {...}
  
  public function getTitle(): string {...}
  public function SetTitle( string $title ) {...}
}

class Item
{
  public function getId(): int {...}
  public function setId( $id ) {...}
  
  public function getDescription(): string {...}
  public function setDescription( string $description ) {...}
  
  public function getCatalogId(): int {...}
  public function setCatalogId( int $id ) {...}
  
  // Here is the important part
  public function getCatalog(): Catalog {...}
  public function setCatalog( Catalog $catalogEntity ) {...}
}
```

And the Item class will automatically synchronize `getCatalog()`, `setCatalog()`, `getCatalogId()`, `setCatalogId()` methods between the the 'items.catalog_id' field and related Catalog Entity object:

```php
// New Catalog, so it doesn't have an ID yet
$catalog = $catalogManager->createEntity();
$catalog->setTitle("Nice catalog");
$catalog->getId(); // catalogs.id=null

// New Item, so it doesn't have a Catalog yet
$item = $itemManager->createEntity();
$item->getCatalog(); // null
$item->getCatalogId(); // items.catalog_id=null

// Let's give to the Item its Catalog
$item->setCatalog( $catalog );

// Well, the Catalog exists, but it wasn't saved yet, so still doesn't have ID
$item->getCatalog(); // Catalog entity
$item->getCatalogId(); // items.catalog_id=null

// Let's save the Catalog now
$catalogManager->save($catalog);
$catalog->getId(); // catalogs.id=123

// And magically, the Item's foreign key has been updated too
$item->getCatalogId(); // items.catalog_id=123

// Let's delete the Catalog
$catalogManager->delete( $catalog );

// And magically, the Item's foreign key has been set to null too
$item->getCatalogId(); // items.catalog_id=null

// Let's change the catalog_id field of the Item
$item->setCatalogId( 5 );

// When we get the Catalog Entity from the Item now, 
// it will fetch that Catalog(id=5) from the db
$anotherCatalog = $item->getCatalog();
$anotherCatalog->getId(); // catalogs.id=5
```

If you have an Entity, and inside it you want to get a different Entity, do the following:

```php
class Item\Manager { /* ... */ }
class Item\Entity { /* ... */ }

class Catalog\Manager { /* ... */ }
class Catalog\Entity {
    public function getCheapestItem(): ?Item\Entity {
        return $this->orm->entityManager( Item\Manager::class, $this->db )
            // in real life you would probably use the QueryBuilder
            ->findOneWhere("catalog_id=? ORDER BY price ASC LIMIT 1", [$this->getId()]);
    }
    public function getAllItems(int $itemId): array {
        return $this->orm->entityManager( Item\Manager::class, $this->db )
            // in real life you would probably use the QueryBuilder
            ->findManyWhere("catalog_id=? ORDER BY number ASC", [$this->getId()]);
    }
}
```

### The Manager

The Manager handles the db operations for 1 table, and reads and writes Entities from/to that db table.

In most frameworks (e.g. Symfony) the Manager is automatically injected into your Controller ("autowired"), so you can use it directly:

```php
public function someFuncInSymfony(CoolioORM\ORM $orm) {
  $userManager = $orm->entityManager( \App\Model\User\Manager::class );
}
```

You can create the Manager with a different database connection by giving a 2nd parameter (see the [Database connectoin section](README.md#database-connections) to see how to create a database connections). This is useful for exaple when you want to work with the same Entity types from your local development database and also from a remote database:

```php
$userManager = $orm->entityManager( 
    \App\Model\User\Manager::class,
    $orm->getDbByUrl( 'mysql://user:password@localhost/other_database_name' )  
);
```

You can change the database connection for a manager (see the [Database connectoin section](README.md#database-connections) to see how to create a database connections). This will automatically clear the [Entity Repository](README.md#optimized-retrieving-the-same-rows-multiple-times-the-entity-repository) (Entitiy Cache) for all the managers:

```php
$manager->setDb( 
    $orm->getDbByUrl( 'mysql://user:password@localhost/other_database_name' )
);
```

In some cases, you might need the Manager at places where you can't inject it (can't "autowire" it):

```php
$orm = CoolioORM\ORM::instance();
$userManager = $orm->entityManager( \App\Model\User\Manager::class );
```

The Manager has the `CoolioORM\ORM` (so you can use other Managers in it to create special relations, etc). So inside any Manager method, you can use:

```php
use App\Model\OtherModel;
class Manager extends CoolioORM\Manager
{
    public function manipulateOtherModel()
    {
        $otherManager = $this->orm->entityManager(OtherModel\Manager::class);
        $otherEntity = $otherManager->findById(123);
        // ... do something with $otherEntity ...
    }
    
    public function someOtherModel_fromArchives()
    {
        $dbArchive = \Milanmadar\CoolioORM\Utils::getDbByUrl( $_ENV['DB_ARCHIVE'] ) // DB_ARCHIVE is an environmental variable (like in .env)
        $otherManager = $this->orm->entityManager(OtherModel\Manager::class, $dbArchive);
        $otherEntity = $otherManager->findById(123);
        // ... do something with $otherEntity ...
    }
    
    public function someOtherModel_fromTheSameDatabaseAsThis()
    {
        $thisDb = $this->db; // the database of this manager
        $otherManager = $this->orm->entityManager(OtherModel\Manager::class, $thisDb);
        $otherEntity = $otherManager->findById(123);
        // ... do something with $otherEntity ...
    }
}
```

#### Retreive Entities from the db (SELECT) with ORM

Note: in most cases you probably want to use the [QueryBuilder](README.md#query-builder).

(to more about the last params (`$forceToGetFromD`) read the [Disable the Entity Repositoy](README.md#disable-the-entity-repositoy) section below)

- `$entity = $manager->findById(123, $forceToGetFromDb)`: Returns a **single Entity** with that id, or NULL if that id is not in the db
- `$entity = $manager->findOneWhere($sqlAfterWhere, $bindParams, $forceToGetFromD)`: Returns a **single Entity** or NULL. The `$sqlAfterWhere` param is only the part that comes after the `WHERE` in the query. 

        $sql = "age > :MinAge "
              ."AND name like :PartialName "
              ."AND country IN (:Countries) "
              ."ORDER BY age LIMIT 1";
        $bindParams = [ 'MinAge'=>18, 'PartialName'=>'%Tom%', 'country'=>['FR','UK','HU'] ];
        $entity = findOneWhere($sql, $bindParams);

    If the `LIMIT 1` was not there in the query, then MySQL would return many rows but the ORM would only return the first Entity anyway (and dispose the rest).
  
- `$entity = $manager->findOne($sql, $bindParams, $forceToGetFromD)`: Returns a **single Entity** or NULL. It's like the one above but the `$sql` param is the entire query.
- `$entitiesArr = $manager->findManyWhere($sqlAfterWhere, $bindParams, $forceToGetFromD)`: Returns an **array of Entity** or an empty array. If works like the  `findOneWhere()` (above)
- `$entitiesArr = $manager->findMany($sql, $bindParams, $forceToGetFromD)`: Returns an **array of Entity** or an empty array. If works like the  `findOne()` (above)
- `$entity = $manager->findByField($fieldname, $value, $forceToGetFromD)`: Returns a **single Entity** or NULL. It checks if that field equals value. It can only to equality, not any other operator. It works like the  `findOne()` (above)

#### Optimized: Retrieving the same rows multiple times (The Entity Repository)

Note: in most cases you probably want to use the [QueryBuilder](README.md#query-builder) (they also use the Entity Repo).

**IMPORTANT:** Topology-enabled tables (tables that has topogemetry fields) will not use the Entity Repository because the topology may change the geometries every time a geometry is added/edited/removed in the topology. 

Internally, the ORM has a Repository of Entities. It's like a cache for Entities: When you fetched an Entity from the db, it will save that Entity to the repository. 
The next time you want to fetch the same Entity, it will use this repository to give you back the same Entity: **giving the exact same object, not another object with the same data in it**.

Let's say there is a row in the db table with values: `id=1` and `something="xyz"`:  
(note in the code, the `===` operator checks if they are the same objects)
```php
$entity_1 = $manager->findById( 1 );
$entity_2 = $manager->findById( 1 ); // no db communication, we just get it from the Repo
($entity_1 === $entity_2); // true

$entity_3 = $manager->findOneWhere("id=1"); // no db comm
($entity_1 === $entity_3); // true

$sql = "something='xyz'"; // this also returns 'id'=1 row from the db  
$entity_4 = $manager->findOneWhere($sql); // YES db comm happens because the Entity Repo only skips db when we use the primary `id` field
($entity_1 === $entity_4); // true, because the ORM knows its the same entity (with id=1)
```

It also works when you do it with many results:
```php
$entity_1 = $manager->findById( 123 );

$entityArr = $manager->findMany("SELECT * FROM tbl"); // select all (including 123 too)
foreach($entityArr as $ent) {
  if($ent->getId() == 123) // this is row with id=123 from the "select all" query
  {
    ($ent === $entity_1) // true 
  }
}
```

Why is it so good? Because it behaves as it should. Let's say there is a Catalog with id=1, and in this catalog there is an Item with id=123; 
```php
// First, fetch all the items in the catalog (among these there is also Item with id=123)
$itemsFromCatalog = $itemManager->findManyWhere("catalog_id=1");

// Then, unrelated to the previous fetch (somewhere else in the code, e.g. inside some deeply hidden method)
// we change the title of Item with id=123 
$itemToFix = $itemManager->findById( 123 ); // ORM gives us the same Item object it alrady has from the "catalog_id=1" result
$itemToFix->setTitle("I change it!");

// Now, list those we got from the catalog (in the first line)
foreach($itemFromCatalog as $item) {
  echo $item->getTitle()."\n"; // For Item 123 it will echo "I change it!"
}
```

Another benefit is that less db communication happens. See this:
```php
// In the code you are writing
$itemArr = $itemManager->findManyWhere("catalog_id=100");
foreach($itemArr as $item)
{
  // This next line will only fetch from the db the first time it's called.
  // Every other time it will NOT talk to the db at all,
  // it will just give you the same $shop Entity from the Repo (much faster).
  $shop = $shopManager->findById( $item->getShopId() );
}
```

#### Disable the Entity Repositoy

So all that is good. But maybe you you don't want the cached Entities from the Repo, but you really want to fetch the data from the database every time you do a `$manager->find*()`. There are 2 ways of doing that...

You can disable the usage of the EntityReposity for a Manager queries: `$manager->setUseEntityRepositry( false )`.

Or you can you can use the last param of the `$manager->find*()`: passing `true` there will force them to fetch the data from the database and skip the EntityRepositry for that one call.

```php
$entA = $manager->findById( 1 );
$entB = $manager->findById( 1, true );
($entA === $entB) /// false, because its a different PHP object
```

#### <span style="color:red">**ATTENTION!**</span>

To to prevent memory limit crash, max 20,000 Entities can be stored at once in the Entity Repo (actually its the `COOLIO_ORM_ENTITY_REPO_MAX_ITEMS` environment variable). After that the Repo will clear the Entity cash (for the table with the most Entities). Then the Repo will continue caching, so the caching benefits will come back.

If you want to control when exactly to clear the Entity Repo cache, you can do this: `$manager->clearRepository(bool)`. The bool param controls if you want to clear the entire repo (true), or only for the table that is related to that Manager (false).

```php
// Process all the items (millions) 
$lastId = 0;
do {
  // We fetch 4000 items in each loop
  $sql = "id > :lastId ORDER BY id ASC LIMIT 4000";
  $itemsArr = $itemManager->findManyWhere($sql, ['LastId'=>$lastId]);
  foreach($itemsArr as $item) {
  $lastId = $item->getId();
    // ... process item ...
  }
  
  // Clear the Items cache right now (just because i want to)
  $manager->clearRepository(false);
}
while(count($itemsArr));
```


#### Create new Entities with ORM

- Without data: `$entity = $manager->createEntity()` (you can pass an empty array, it's the same as not passing anything). It creates a new entity with default values (taken from the db `Manager->getDefaultValues()` which was generated from table definition by the scaffolder). If there is no default value for a given field, then it will be NULL. 
- With correctly typed PHP data: `$entity = $manager->createEntity( $phpTypedData )` creates a new entity using the given data as it was given (no type conversions).  
  The `$phpTypedData` param is an associative array. Keys are the column names of the db table, values are the values to set.  
  Passing data to it means the default values (from the db table definition) will not be used at all (so if you pass some data, but you omit a field that has a default value defined in the db table definition as DEFAULT, the ORM will NOT use that default values).    
  Passing an empty array is the same as omitting the parameter (see the first list point).
- With data from the db: `$entity = $manager->createEntity( $mysqlRowData )` It will first convert the given data values to their PHP data types (because everything from the db would come as a string). Then it will create the new entity as with the `createEntity( $phpTypedData )` (see the above list point).  
  Passing an empty array is the same as omitting the parameter (see the first list point).
  

#### Save Entities to the db (INSERT, UPDATE) with ORM

The `$manager->save( $entity )` method saves the Entity into the database. [Related entities](README.md#entity-relations) are saved automatically inside `parent::save()`.

**INSERT or UPDATE?**  
You know every table must have a primary 'id' field.  
So if the 'id' field is NULL (`is_null( $entity->getId() )`) it means the data (the Entity) was not yet saved to the db, so the Manager will perform an INSERT query.  
If the 'id' is not NULL then UPDATE.  

#### Delete Entities from the db (DELETE) with ORM

The `$manager->delete( $entity )` method deletes the Entity from the database. **Note:** the [related entities](README.md#entity-relations) are not deleted automatically, so you need to delete them with their own Manager in the delete() method of your Manager.

Let's say we delete a Catalog that has Items in it. In that case we want to delete all the items too.

```php
# src/Model/Catalog/Manager.php
namespace Model/Catalog;
class Manager extends CoolioORM\Manager
{
    /**
     * @inheritDoc
     * @param Entity $ent
     */
    public function delete(CoolioORM\Entity $ent): void
    {
        if( ! $ent instanceof Entity) {
            throw new \InvalidArgumentException(get_class($this)."::delete() can't delete ".get_class($ent));
        }
        
        // The Catalog has an ID which means it was saved already earlier,
        // so it may have items, so let's delete those items
        if( $ent->getId() ) {
            $itemManager = $this->orm->entityManager( \App\Model\Item\Manager::class );
            $items = $itemManager->findManyWhere('catalog_id = :CatId', ['CatId'=>$ent->getId()]);
            foreach($items as $item) {
                // Delete the Item
                $itemManager->delete( $item );
                
                // Another case could be to set their catalog_id to NULL
                //$item->setCatalog( Null );
                //$itemManager->save( $item );
            }
        }
        
        // Delete the Catalog from the db
        parent::delete($ent);
    }
}
```

Once an Entity is deleted the `$entity` object still exists, but it's marked as deleted. So calling setters after deleting will throw a `\LogicException` (you can still call getters). If you need the old id of the deleted Entity, you can do `$entity->_getDeletedId()` (notice the underscore).

To delete several rows with WHERE clause we must use the [QueryBuilder](README.md#write-data-with-the-querybuilder).

You can TRUNCATE an entire table with `$manager->truncate()` (check it's parameters in the code).

#### Rollback and Commit

Note: once you called `$manager->save($entity)` or `$manager->delete($entity)` you cannot rollback to an earlier state of data (because it's already written to the db and committed there too).

The Entities "remember their data" at certain checkpoints. That checkpoint is called a "commit". Commits happen when:

- When the Entity is created (either manually or from the db)
- When the Entity is saved with `$manager->save()`
- You can also create a "checkpoint" at any moment with `$entity->_commit()`

You can tell the Entity to set all of its data back to how it all was at the last commit: `$entity->_rollback()`. You can only rollback to the last commit (in other words: a commit overwrites the previous commit).

#### It's all optimized automatically

**Saving only what changed**  
Internally, the Entity knows which fields have changed (`$entity->_getDataChanged()`). So when we save it with the Manager, it will only UPDATE the changed fields (if nothing changed, no db action will happen).

**Prepared statements caching**  
Prepared statements are needed against <a href="https://www.w3schools.com/sql/sql_injection.asp" target="_blank">SQL Injections</a>. But they need an extra roundtrip between the db and PHP, so it can be slower. However, this ORM caches the prepared statements, so running the same SQL queries (with various parameters) is fast (faster then not having prepared statements).

---

## Database connections

Note: in most cases you probably want to use the [Entity Managers](README.md#entity-manager-orm) or the [QueryBuilder](README.md#query-builder).

The Database Abstraction Layer (DBAL) is a set of classes that directly communicate with the database. We use <a href="https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/index.html" target="_blank">Doctrine DBAL v4.2</a>.  
When you SELECT data it typically returns a collection of associative arrays (although the ORM layer can return Entities).

We use <a href="https://dev.mysql.com/doc/connector-j/8.0/en/connector-j-reference-jdbc-url-format.html" target="_blank">Connection Urls</a> to connect to the db. Here is one:  
`$connUrl = 'mysql://user:password@localhost/database_name'`

There are several ways to create a db connection.

The below code snippets assume that `CoolioORM\ORM $orm` were injected to your method by Symfony.

- **The hardcore way:**    
  At the lowest layer we have a <a href="https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html#configuration" target="_blank">Doctrine database connection</a> class that does the actual communication with the database:  
  `$db = \Doctrine\DBAL\DriverManager::getConnection(['url'=>$connUrl])`    
  So you can create one like that, but there is an easier way...
- **The easy way:**    
  Use our ORM:     
  `$db = $orm->getDbByUrl( $connUrl )`  
  So you can create one like that too, but there is an easier way...
- **Get it from a Manager:**  
  In some cases, you want to re-use a connection from an object that is already using a connection. E.g. the Managers:  
  `$db = $orm->entityManager( \App\Model\Person\Manager::class )->getDb()`  
  Btw, the Manger can give you other details about it's database attributes. Start writing `$manager->getDb` in your IDE to get the suggestions.
- **Get it from a QueryBuilder:**  
  `$db = $queryBuilder->getConnection()` (Sorry it's not the expected `->getDb()`, it's Doctrine.)  
  (You can't change the db connection for an existing QueryBuilder, Doctrine doesn't allow it.)
  
#### Retreiving data with the Database object

Note: in most cases you probably want to use the [Entity Managers](README.md#entity-manager-orm) or the [QueryBuilder](README.md#query-builder).

Note: the below methods at the Database layer can't hande parameterized queries when the parameter value type is array (`$db->executeQuery('...WHERE field IN (:ArrayList)', ['ArrayList'=>[1,2,3]])` will fail). For that, use the [QueryBuilder](README.md#retrieve-data-with-the-querybuilder) or the [Manager](README.md#retreive-entities-from-the-db-select-with-orm).

To get the results of SELECT queries:

- `$db->executeQuery($sql, $param)` returns a `\Doctrine\DBAL\Result` object. That also has many methods, including the ones listed below.
- `$db->fetchAssociative($sql, $params)` returns the first row of the result as an associative array (or `false` if there was no match)
- `$db->fetchAllAssociative($sql, $params)` returns the result as an array of associative arrays (or empty array if there was no match)
- `$db->fetchAllAssociativeIndexed($sql, $params)` returns the result as an associative array with the keys mapped to the first column and the values being an associative array representing the rest of the columns and their values
- `$db->fetchAllKeyValue($sql, $params)` returns the result as an associative array with the keys mapped to the first column and the values mapped to the second column
- `$db->fetchAllNumeric($sql, $params)` returns the result as an array of numeric arrays
- `$db->fetchFirstColumn($sql, $params)` returns the result as an array of the first column values
- `$db->fetchNumeric($sql, $params)` returns the first row of the result as a numerically indexed array
- `$db->fetchOne($sql, $params)` returns the value of a single column of the first row of the result

See <a href="https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html#api" target="_blank">Doctrine docs</a> for more.

#### Write data with the Database object

Note: in most cases you probably want to use the [Entity Managers](README.md#entity-manager-orm) or the [QueryBuilder](README.md#query-builder).

To execute INSERT, UPDATE, DELETE and other queries that don't return results:  
`$db->executeStatement($sql, $params)`

See <a href="https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/data-retrieval-and-manipulation.html#api" target="_blank">Doctrine docs</a> for more.

---

## Query Builder

The Query Builder is a class that provides a simple object oriented interface to write SQL queries. It can also execute them and return the results in many different formats. 

CoolioORM's QueryBuilder extends the <a href="https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/query-builder.html" target="_blank">Doctrine QueryBuilder</a>, so it's very similar to it, but the CoolioORM QueryBuilder adds some more functionality and comfort and speed.

#### Create a query builder

The QueryBuilder really only builds queries. To actually execute those queries on a db, it needs a database connection (see the [Database connection section](README.md#database-connections) above to see how to create a db connection).

If you want a general QueryBuilder that can return Entites from the db (not just raw associative arrays), then you also need to give a Manager to the QueryBuilder.

There are several ways of creating a QueryBulder:

- `$sqlBldr = $manager->createQueryBuilder()`: this will be able to give you Entities. For this, the `->select('*')` and the `->from( $manager->getDbTable() )` is already set as default. (You can also use it as a general purpose QueryBuilder by calling the `->from('different_table)` method, and return associative arrays with the `->fetchAssociative()`).  
- `$sqlBldr = $orm->createQueryBuilderByConnectionUrl( $connUrl )`: it will use the db connection with the given connection url string
- `$sqlBldr = $orm->createQueryBuilderByConnection( $db )`: it will use the given db connection

#### Building a query

Below code only shows the basic usage.

```php
$sqlBldr
    ->select('name, age')
    ->from('persons')
    ->where('age > :Age AND height < :Height')->setParameter('Age', 18)->setParameter('Height', 175)
    ->orWhere('something IN (:ArrayVal)')->setParameter('ArrayVal', ['a','b','c'])
    ->andWhere('name LIKE :Part')->setParameter('Part', '%tom%')
    ->groupBy('category')->addGroupBy('subcategory')
    ->having('children > :ChildNum')->setParameter('ChildNum', 1)
    ->orHaving('married = 1')
    ->andHaving('iq > 115')
    ->orderBy('popularity', 'desc')->addOrderBy('age', 'asc')
    ->limit(0, 100);
```
You can also set all the paremeter values at once (don't use this with `whereColumn()`, `andWhereColumn()` and `orWhereColumn()`):

```php
->setParameters([
    'Age'=>18,
    'ArrayVal'=>['a','b','c'],
    ... 
]);
```

To handle cases when you are not sure if your value is NULL, you can use the following `whereColumn()` comfort methods (note: you can't change their values later with `->setParameter()`)):

The `whereColumn()` methods will also automatically detect array values, and convert '=' to 'IN'.

```php
$intOrNullValue = $someEntity->getSomething();
$sqlBldr
    // inside the '=' will be changed to 'IS NULL' if $intOrNull value is NULL
    ->whereColumn('field_name_a', '=', $intOrNull)
    // inside the '!=' will be changed to 'IS NOT NULL' if $intOrNull value is NULL
    ->andWhereColumn('field_name_b', '!=', $intOrNull)
    ->orWhereColumn('field_name_c', '=', $intOrNull)
    ->orWhereColumn('field_name_d', '!=', [1,2,3,]); // '!=' converts to 'NOT IN'
```

See the <a href="https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/query-builder.html#high-level-api-methods" target="_blank">Doctrine docs</a> for more, including <a href="https://www.doctrine-project.org/projects/doctrine-orm/en/2.9/reference/query-builder.html#the-expr-class" target="_blank">complex expressions</a>.

You can see the SQL string of the resulting query:  
`$sqlBldr->getSQL()` and `$sqlBldr->getSQLNamedParameters()`

#### Retrieve data with the QueryBuilder

**To return Entities** your QueryBuilder also needs to have a Manager (see [Create a query builder](README.md#create-a-query-builder) above):

- `$sqlBldr->fetchOneEntity()` returns 1 Entity
- `$sqlBldr->fetchManyEntity()` returns an array of Entities

**Returning raw data (not Entities)** works the same as described in the [Database object section](README.md#retreiving-data-with-the-database-object), except the QueryBuilder methods don't need any parameters:

- `$sqlBldr->executeQuery()` returns a `\Doctrine\DBAL\Result` object (that object also has many methods, including the ones listed below).
- `$sqlBldr->fetchAssociative()` returns the first row of the result as an associative array (or `false` if there was no match)
- `$sqlBldr->fetchAllAssociative()` returns the result as an array of associative arrays (or empty array if there was no match)
- For the rest see the [Database object section](README.md#retreiving-data-with-the-database-object)

#### Write data with the QueryBuilder

Note: The below operations will modify data in the database, so the EntityRepository (aka. the Entity cache) will be cleared (for all db tables).

- INSERT:

        $sqlBldr->insert('table_name')
                ->setValue('age', ':Age')->setParameter('Age', 18)
                ->setValue('name', :Name')->setParameter('Name', "Jhonny")
                ->executeStatement();
  
- UPDATE:
  
        $sqlBldr->update('table_name')
                ->set('age', ':Age')->setParameter('Age', 18)
                ->set('name', ':Name')->setParameter('Name', "Jhonny")
                ->where('id = :Id')->setParameter('Id', 1)
                ->executeStatement();

- DELETE:
  
        $sqlBldr->delete('table_name')
               ->where('id=1')
               ->andWhere('age>18 OR name=:Name')->setParameter('Name', "Jhonny")
               ->executeStatement();

---

## Scaffold

Scaffolding is the process of generating PHP code from a database table. It will generate the Model Manager, Entity, Controller and View for you.

To generate the files, run `bin/console app:scaffold` (it will guide you through the process)

The result will be in `/_dev/scaffold/out`. You can take the inner contents of that folder as it is and copy it into the project root. Everything will go into its correct place.

### Rules for the database tables

- Comment every field (MySQL). They will become the PHP method descriptions in DocBlocks
- Every db table MUST have primary id called 'id': MySQL: `'id' int(11) NOT NULL AUTO_INCREMENT`, PostgreSQL: `id SERIAL PRIMARY KEY`
- Use Foreign Keys properly (the Scaffolder will use them to generate the relations)
- Having a `'create_time' int` or `'created_at'` field will automatically set the default value to `time()` in PHP (in the generated Manager::getDefaultValue() method)


# Contribution Guide

You need a `.env` file in the root of the project. You can copy it from `.env.example` and change the values to your needs. These keys are mandatory: `DB_MYSQL_DB1`, `DB_MYSQL_DB2`, `DB_POSTGRES_DB1`, `DB_POSTGRES_DB2`.   

