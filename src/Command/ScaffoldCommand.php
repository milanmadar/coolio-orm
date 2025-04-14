<?php

namespace Milanmadar\CoolioORM\Command;

use Milanmadar\CoolioORM\ORM;
use Milanmadar\CoolioORM\Geo\Shape2D;
use Milanmadar\CoolioORM\Geo\ShapeZ;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'coolio-orm:scaffold', description: 'Scaffolds Entity and Manager classes from your database')]
class ScaffoldCommand extends Command
{
    private ORM $orm;

    /** @var array<string> */
    private array $uses;

    public function __construct(ORM $orm)
    {
        $this->orm = $orm;
        $this->uses = [];

        // Keep this at the end of this constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('coolio-orm:scaffold');
        $this->setDescription('Scaffolds Entity and Manager classes from your database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        //
        // find the templates
        foreach(['vendor/milanmadar/coolio-orm/scaffold_templates', 'scaffold_templates'] as $dir) {
            if(is_dir(getcwd().'/'.$dir)) {
                $templatesDir = getcwd().'/'.$dir;
                break;
            }
        }
        if(empty($templatesDir)) {
            $io->error('ERROR: No scaffold templates found. Please run "composer install" to install the package.');
            return Command::FAILURE;
        }

        //
        // database
        $dbSelectors = [];
        $ENVkeys = array_keys($_ENV);
        foreach($ENVkeys as $k) {
            if(str_starts_with($k, 'DB_')) {
                $dbSelectors[] = $k;
            }
        }
        if(empty($dbSelectors)) {
            $io->error('ERROR: No database connection found in in your environment variables ($_ENV). Please add an envirnmental variable that starts with "DB_" and sets the connection string url, like DB_DEFAULT=pdo-mysql://username:password@127.0.0.1/database_name');
            return Command::FAILURE;
        }

        $dbSelect = $dbSelectors[0];
        if(count($dbSelectors) > 1) {
            $dbSelect = $io->choice('Database', $dbSelectors, $dbSelectors[0]);
        }

        $db = $this->orm->getDbByUrl($_ENV[$dbSelect]);
        $sm = $db->createSchemaManager();

        //
        // table
        do {
            $tables = $sm->listTableNames();
            $tbl = $io->choice('Table', $tables);

            $tableColumns = $sm->listTableColumns($tbl);
            if (empty($tableColumns)) {
                $io->error('ERROR: No such table in the selected database. Try again');
            }
        } while(empty($tableColumns));

        // PostgreSQL: We need the schema
        $IS_POSTGIS = str_contains($_ENV[$dbSelect], 'pgsql') && $db->getDatabasePlatform()->hasDoctrineTypeMappingFor('geometry');
        $POSTGIS_SCHEMA = null;
        if($IS_POSTGIS) {
            // if the table name has a dot, then thats the schema name
            if(strpos($tbl, '.')) {
                $POSTGIS_SCHEMA = explode('.', $tbl)[0];
            } else {
                $POSTGIS_SCHEMA = $db->executeQuery("SELECT current_schema()")->fetchOne();
                $tbl = $POSTGIS_SCHEMA . '.' . $tbl;
            }
        }

        //
        // try to guess the model name with the full namespace
        $guessModelName = str_replace(' ', '', ucwords(str_replace(['-','_'], ' ', strtolower($tbl))));
        if(strpos($guessModelName, 'Public.') === 0) {
            $guessModelName = explode('.', $guessModelName)[1];
            $guessModelName = ucfirst($guessModelName);
        } else {
            $guessModelName = str_replace(' ', '_', ucwords(str_replace('.', ' ', $guessModelName)));
        }
        $cwd = getcwd();
        $modelsDir = $cwd.'/src/Model';
        $bestModelNameGuessFound = false;
        if(is_dir($cwd.'/src')) {
            if(is_dir($cwd.'/src/Model'))
            {
                /** @var array<string> $glob */
                $glob = glob($cwd.'/src/Model/*');
                $subDirs = array_filter($glob, 'is_dir');
                if (!empty($subDirs)) {
                    $firstSubdir = basename(reset($subDirs));
                    if(file_exists($cwd.'/src/Model/'.$firstSubdir.'/Entity.php')) {
                        /** @var string $content */
                        $content = file_get_contents($cwd.'/src/Model/'.$firstSubdir.'/Entity.php');
                        $lines = explode("\n", $content);
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if (str_starts_with($line, 'namespace')) {
                                $line = str_replace(["\t","\r"], ' ', $line);
                                $line = str_replace(['    ','   ','  '], ' ', $line);
                                $parts = explode(' ', $line);
                                $namespace = trim($parts[1], ";\n\t\r,\\ ");
                                $parts = explode('\\', $namespace);
                                array_pop($parts);
                                $namespace = implode('\\', $parts);
                                $guessModelName = $namespace . '\\' . $guessModelName;

                                $bestModelNameGuessFound = true;

                                break;
                            }
                        }
                    }
                }
            }
        }
        if(!$bestModelNameGuessFound) {
            $guessModelName = 'App\\Model\\' . $guessModelName;
        }

        //
        // model name
        $modelName = $io->ask("Name of the new Model (with namespace, like 'App\Model\Product') ", $guessModelName);
        $modelName = str_replace("/", "\\", $modelName);
        $modelName = trim($modelName, "\\");
        $modelName = str_replace('.', '_', $modelName);
        if(str_ends_with($modelName, "Entity")) {
            $modelName = substr($modelName, 0, -6);
            $modelName = rtrim($modelName, '\\');
        }

        //
        // namespace
        $namespace = $modelName;

        //
        // foreign keys (entity relations)
        /* @var $foreigns \Doctrine\DBAL\Schema\ForeignKeyConstraint */
        $foreigns = [];
        $fkeys = $sm->listTableForeignKeys($tbl);
        foreach($fkeys as $fkey)
        {
            $fkName = $fkey->getName();

            $localColumns = $fkey->getLocalColumns();
            $otherTbl = $fkey->getUnqualifiedForeignTableName();
            $foreignColumns = $fkey->getUnquotedForeignColumns();
            $thisFldName = $localColumns[0];
            $otherFld = $foreignColumns[0];

            $io->write("<info>\n ".$thisFldName . ': Foreign Key to "' . $otherTbl . '.' . $otherFld.'":</>');

            if (count($localColumns) != 1) {
                $io->error("WARNING: The following foreign key has more then 1 column. ORM can't handle that automatically: " . $fkName);
                $ok = $io->confirm("The following foreign key has more then 1 column. ORM can't handle that automatically: " . $fkName . ". Ok? [Y=fine go on , N=exit now] ", false);
                if ($ok) continue;
                else return Command::FAILURE;
            }
            if (count($foreignColumns) != 1) {
                $io->error("WARNING: The following foreign key references more then 1 column. ORM can't handle that automatically: " . $fkName);
                $ok = $io->confirm("The following foreign key has more then 1 column. ORM can't handle that automatically: " . $fkName . ". Ok? [Y=fine go on , N=exit now] ", false);
                if ($ok) continue;
                else return Command::FAILURE;
            }
//            if ($otherFld != 'id') {
//                $ok = $io->confirm("WARNING: The following foreign key is referencing a key other then 'id'. ORM can't handle that automatically so you must add it manually: " . $fkName . ". Ok? [Y=will do , N=exit now] ", false);
//                if ($ok) continue;
//                else return Command::FAILURE;
//            }

            // related entity
            do {
                // related entity name
                $guessRelatedModelName = str_replace(' ', '', ucwords(str_replace(['-','_'], ' ', $otherTbl)));
                $parts = explode('\\', $modelName);
                array_pop($parts);
                $ns = implode('\\', $parts);
                $guessRelatedModelName = $ns . '\\' . $guessRelatedModelName;

                $otherModelName = $io->ask($thisFldName.": Name of the related Model (full class name with namespace, writing 'Entity' at the end desn't matter): ", $guessRelatedModelName);
                $otherModelName = str_replace("/", "\\", $otherModelName);
                $otherModelName = trim($otherModelName, '\\');
                if(str_ends_with($otherModelName, "Entity")) {
                    $otherModelName = substr($otherModelName, 0, -6);
                    $otherModelName = rtrim($otherModelName, '\\');
                }
                $otherModelName_Entity = $otherModelName . '\\Entity';

                $ok = class_exists('\\'.$otherModelName_Entity);
                if(!$ok) {
                    $ok = $io->confirm("Class doesn't exist: " . $otherModelName_Entity.". Ok? [Y=use that anyway, N=type again] ", false);
                }
            } while(!$ok);

            $parts = explode('\\', $otherModelName);
            $otherModelName_withoutNS_withoutEntity = array_pop($parts);

            $foreigns[$thisFldName] = [
                'otherTbl' => $otherTbl,
                'otherFld' => $otherFld,
                'otherModelName' => $otherModelName,
                'otherModelName_withoutNS_withoutEntity' => $otherModelName_withoutNS_withoutEntity,
                'otherModelName_asParam' => lcfirst($otherModelName_withoutNS_withoutEntity)
            ];
        }

        //
        // Entity accessors AND Manager field types AND Manager default values
        $attributeDefs = '';
        $accessorMethodsSrc = '';
        $mgrFldTypes = '';
        $mgrTopogeoFldTypes = '';
        $mgrDefVals = '';

        foreach($tableColumns as $col)
        {
            $colName = $col->getName();
            $colDefVal = $col->getDefault();
            $colNullable = !$col->getNotnull();
            $colComment = $col->getComment();
            if(empty($colComment)) $colComment = '';

            // for Postgis coltype, we need 'geometry(point...'
            $geoShapeType = null;
            if($IS_POSTGIS)
            {
                if(strpos($tbl, '.')) {
                    $relName = explode('.', $tbl)[1];
                } else {
                    $relName = $tbl;
                }

                $nativeColType = strtolower($db->executeQuery("
                    SELECT pg_catalog.format_type(a.atttypid, a.atttypmod) AS full_data_type
                    FROM pg_catalog.pg_attribute a
                    JOIN pg_catalog.pg_class c ON a.attrelid = c.oid
                    JOIN pg_catalog.pg_namespace n ON c.relnamespace = n.oid
                    WHERE c.relname = '".$relName."'
                      AND n.nspname = '".$POSTGIS_SCHEMA."'
                      AND a.attname = '".$colName."'
                      AND a.attnum > 0
                      AND NOT a.attisdropped
                ")->fetchOne());
                $colType = $nativeColType;

                if(str_starts_with($nativeColType, 'topogeometry'))
                {
                    /** @var array<string, mixed> $topoGeo_field_info_row */
                    $topoGeo_field_info_row = $db->executeQuery("
                        SELECT *
                        FROM topology.layer
                        WHERE schema_name = '".$POSTGIS_SCHEMA."'
                          AND table_name = '".$relName."'
                          AND feature_column = '".$colName."'
                    ")->fetchAssociative();

                    $geoShapeType = match((int)$topoGeo_field_info_row['feature_type']) {
                        1 => Shape2D\MultiPoint::class,
                        2 => Shape2D\MultiLineString::class,
                        3 => Shape2D\MultiPolygon::class,
                        default => Shape2D\GeometryCollection::class
                    };

                    // info for the Manager::getTopoGeometryFieldInfo()
                    $topology_layer = (int)$topoGeo_field_info_row['layer_id'];
                    $tolerance = '$_ENV["TOPOGEOMETRY_DEFAULT_TOLERANCE"]';
                    $topology_name = $db->executeQuery("
                        SELECT name
                        FROM topology.topology
                        WHERE id = ".$topoGeo_field_info_row['topology_id']
                    )->fetchOne();

                    $mgrTopogeoFldTypes .= "\n        '".$colName."' => [\n";
                    $mgrTopogeoFldTypes .= "            'topology_name'  => '".$topology_name."',\n";
                    $mgrTopogeoFldTypes .= "            'topology_layer' => ".$topology_layer.",\n";
                    $mgrTopogeoFldTypes .= "            'tolerance'  => ".$tolerance."\n";
                    $mgrTopogeoFldTypes .= "        ],";
                }
                elseif(str_starts_with($nativeColType, 'geometry'))
                {
                    $colType = 'geometry';

                    // 2d
                    if(str_contains($nativeColType, '(point,')) {
                        $geoShapeType = Shape2D\Point::class;
                    } elseif(str_contains($nativeColType, '(linestring,')) {
                        $geoShapeType = Shape2D\LineString::class;
                    } elseif(str_contains($nativeColType, '(polygon,')) {
                        $geoShapeType = Shape2D\Polygon::class;
                    } elseif(str_contains($nativeColType, '(multipoint,')) {
                        $geoShapeType = Shape2D\MultiPoint::class;
                    } elseif(str_contains($nativeColType, '(multilinestring,')) {
                        $geoShapeType = Shape2D\MultiLineString::class;
                    } elseif(str_contains($nativeColType, '(multipolygon,')) {
                        $geoShapeType = Shape2D\MultiPolygon::class;
                    } elseif(str_contains($nativeColType, '(geometrycollection,')) {
                        $geoShapeType = Shape2D\GeometryCollection::class;
                    } elseif(str_contains($nativeColType, '(circularstring,')) {
                        $geoShapeType = Shape2D\CircularString::class;
                        $colType = 'geometry_curved';
                    } elseif(str_contains($nativeColType, '(compoundcurve,')) {
                        $geoShapeType = Shape2D\CompoundCurve::class;
                        $colType = 'geometry_curved';
                    } elseif(str_contains($nativeColType, '(curvepolygon,')) {
                        $geoShapeType = Shape2D\CurvePolygon::class;
                        $colType = 'geometry_curved';
                    } elseif(str_contains($nativeColType, '(multicurve,')) {
                        $geoShapeType = Shape2D\MultiCurve::class;
                        $colType = 'geometry_curved';
                    }

                    // 3d
                    elseif(str_contains($nativeColType, '(pointz,')) {
                        $geoShapeType = ShapeZ\PointZ::class;
                    } elseif(str_contains($nativeColType, '(linestringz,')) {
                        $geoShapeType = ShapeZ\LineStringZ::class;
                    } elseif(str_contains($nativeColType, '(polygonz,')) {
                        $geoShapeType = ShapeZ\PolygonZ::class;
                    } elseif(str_contains($nativeColType, '(multipointz,')) {
                        $geoShapeType = ShapeZ\MultiPointZ::class;
                    } elseif(str_contains($nativeColType, '(multilinestringz,')) {
                        $geoShapeType = ShapeZ\MultiLineStringZ::class;
                    } elseif(str_contains($nativeColType, '(multipolygonz,')) {
                        $geoShapeType = ShapeZ\MultiPolygonZ::class;
                    } elseif(str_contains($nativeColType, '(geometrycollectionz,')) {
                        $geoShapeType = ShapeZ\GeometryCollectionZ::class;
                    } elseif(str_contains($nativeColType, '(circularstringz,')) {
                        $geoShapeType = ShapeZ\CircularStringZ::class;
                        $colType = 'geometry_curved';
                    } elseif(str_contains($nativeColType, '(compoundcurvez,')) {
                        $geoShapeType = ShapeZ\CompoundCurveZ::class;
                        $colType = 'geometry_curved';
                    } elseif(str_contains($nativeColType, '(curvepolygonz,')) {
                        $geoShapeType = ShapeZ\CurvePolygonZ::class;
                        $colType = 'geometry_curved';
                    } elseif(str_contains($nativeColType, '(multicurvez,')) {
                        $geoShapeType = ShapeZ\MultiCurveZ::class;
                        $colType = 'geometry_curved';
                    }

                    else {
                        $io->error("ERROR: Unknown geometry type: ".$nativeColType);
                        return Command::FAILURE;
                    }
                }

                // this happens to non-geo native types like 'character varying(45)' 'numeric(8,2)' 'character(8)'
                $colType = explode(' ', $colType)[0];
                $colType = explode('(', $colType)[0];
            }
            else
            {
                $colType = strtolower($col->getType()->getBindingType()->name);
            }

            // primary id
            if($colName == 'id') {
                $mgrFldTypes .= "\n        '".$colName."' => 'integer',";
                continue;
            }

            // default values
            if(!is_null($colDefVal) && $colType != 'geometry' && $colType != 'geometry_curved' && $colType != 'topogeometry')
            {
                if($colType == 'string' || $colType == 'text' || $colType == 'character' || $colType == 'char' || $colType == 'varchar') {
                    $defValSrc = "'".str_replace("'", "\\'", $colDefVal)."'";
                } else {
                    $defValSrc = $colDefVal;
                }
                $mgrDefVals .= "\n        '".$colName."' => ".$defValSrc.",";
            }
            elseif($colName == 'create_time' || $colName == 'created_at')
            {
                $mgrDefVals .= "\n        '".$colName."' => time(),";
            }
            $hasDefVal = !empty($mgrDefVals);

            // Entity accessors
            switch($colType)
            {
                case 'boolean': case 'smallint': case 'tinyint':
                    $isBool = $io->confirm("\n ".$colName.' is "'.$colType.'" in the database. Is that bool or int in php? [Y=bool , N=int] ', false);
                    if($isBool) {
                        $io->writeln(" ".$colName.": boolean");
                        $colType = 'boolean';
                        $paramType = 'bool';
                        $docParamType = 'bool';
                    } else {
                        $io->writeln(" ".$colName.": integer");
                        $colType = 'integer';
                        $paramType = 'int';
                        $docParamType = 'int';
                    }
                    break;
                case 'integer':
                case 'bigint':
                    $paramType = 'int';
                    $docParamType = 'int';
                    break;
                case 'float':
                case 'decimal':
                case 'numeric':
                case 'real':
                case 'double':
                    $paramType = 'float';
                    $docParamType = 'float';
                    break;
                case 'json':
                case 'json_array':
                    $paramType = 'array';
                    $docParamType = 'array<string|int, mixed>';
                    break;
                case 'array':
                case 'simple_array':
                    $paramType = 'array';
                    $docParamType = 'array<string|int>';
                    break;
                case 'string':
                case 'varchar':
                case 'char':
                case 'character':
                    $colType = 'string';
                    $paramType = 'string';
                    $docParamType = 'string';
                    break;
                case 'text':
                    $paramType = 'string';
                    $docParamType = 'string';
                    break;
                case 'geometry':
                case 'geometry_curved':
                case 'topogeometry':
                    $_ = str_replace('Milanmadar\CoolioORM\Geo\\', '', (string)$geoShapeType);
                    $paramType = $_;
                    $docParamType = $_;
                    if(str_contains($_, 'ShapeZ')) {
                        $this->addUses('Milanmadar\CoolioORM\Geo\ShapeZ');
                    } else {
                        $this->addUses('Milanmadar\CoolioORM\Geo\Shape2D');
                    }
                    break;
                default:
                    exit("Can't scaffold column type: '".$colType."'\n");
            }

            $mgrFldTypes .= "\n        '".$colName."' => '".$colType."',";

            //
            $methodName = str_replace(' ', '', ucwords( str_replace(['_','-'], ' ', strtolower($colName) ) ) );

            //
            $nullableQMark = $colNullable ? '?' : '';
            $nullableQMarkGetter = $hasDefVal ? '' : $nullableQMark;
            $docReturnTypeGetter = $docParamType . ($hasDefVal ? '' : ($colNullable ? '|null' : ''));
            if($colNullable) $docParamType .= '|null';

            //
            $docMethodDescription = ucfirst($colComment);

            // It's a Foreign Key
            if(isset($foreigns[$colName]))
            {
                $otherFld = $foreigns[$colName]['otherFld'];
                $otherEntityClassFull = '\\'.$foreigns[$colName]['otherModelName'];
                $otherEntityAttrName = $foreigns[$colName]['otherModelName_asParam'];
                $otherEntityMethodName = $foreigns[$colName]['otherModelName_withoutNS_withoutEntity'];

                $typeMaybeNull = ($colNullable ? '|null' : '');

                //$this->addUses($otherEntityClass);

                $attributeDefs .= "        '".$colName."' => [".$otherEntityClassFull."\\Manager::class, '".$otherFld."'],\n";

                $methodSrc = "    /**
     * Sets the related ".$otherEntityClassFull."\\Entity and synchronizes the '".$colName."' field
     * @param ".$otherEntityClassFull."\\Entity".$typeMaybeNull." $".$otherEntityAttrName."
     * @return $"."this
     */
    public function set".$otherEntityMethodName."(".$nullableQMark.$otherEntityClassFull."\\Entity $".$otherEntityAttrName."): self
    {
        $"."this->_relationSetEntity('".$colName."', $".$otherEntityAttrName."); 
        return $"."this;
    }
    
    /**
     * Returns the related ".$otherEntityClassFull."\\Entity (optized with cache and synchronized with the '".$colName."' field)
     * @return ".$otherEntityClassFull."\\Entity|null
     */
    public function get".$otherEntityMethodName."(): ?".$otherEntityClassFull."\\Entity
    {
        /** @var ".$otherEntityClassFull."\\Entity|null */
        return $"."this->_relationGetEntity('".$colName."');
    }

    /**
     * Tells if there is a related ".$otherEntityClassFull."\\Entity (optized with cache and synchronized with the '".$colName."' field)
     * @return bool
     */
    public function has".$otherEntityMethodName."(): bool
    {
        return $"."this->_relationHasEntity('".$colName."');
    }
    
    /**
     * Sets the '".$colName."' field (the related $"."this->get".$otherEntityMethodName."() will be automatically synchronized as needed)
     * @param ".$paramType.$typeMaybeNull." $"."val The field value of the related ".$otherEntityClassFull."\\Entity
     * @return $"."this
     */
    public function set".$methodName."(".$nullableQMark.$paramType." $"."val): self
    {
        $"."this->_set('".$colName."', $"."val);
        return $"."this;
    }
    
    /**
     * Returns the '".$colName."' field (synchronized from the $"."this->set".$otherEntityMethodName."() if needed)
     * @return ".$paramType.$typeMaybeNull."
     */
    public function get".$methodName."(): ".$nullableQMark.$paramType."
    {
        return $"."this->_get('".$colName."');
    }
    
";
            }
            else // It's NOT a foreign key
            {
                $methodSrc = file_get_contents($templatesDir . '/EntityAccessor.tpl');
                if (empty($methodSrc)) {
                    $io->error('ERROR: No tpl file: ' . $templatesDir . '/EntityAccessor.tpl');
                    return Command::FAILURE;
                }

                $methodSrc = str_replace('_DOC_RETURN_TYPE_GETTER_', $docReturnTypeGetter, $methodSrc);
                $methodSrc = str_replace('_NULLABLE_Q_MARK_GETTER_', $nullableQMarkGetter, $methodSrc);
                $methodSrc = str_replace('_METHOD_NAME_', $methodName, $methodSrc);
                $methodSrc = str_replace('_DOC_METHOD_DESCRIPTION_', $docMethodDescription, $methodSrc);
                $methodSrc = str_replace('_DOC_PARAM_TYPE_', $docParamType, $methodSrc);
                $methodSrc = str_replace('_PARAM_TYPE_', $paramType, $methodSrc);
                $methodSrc = str_replace('_NULLABLE_Q_MARK_', $nullableQMark, $methodSrc);
                $methodSrc = str_replace('_COL_NAME_', $colName, $methodSrc);
            }

            // add this accessor method
            $accessorMethodsSrc .= $methodSrc;
        }

        //
        // Output directory
        do {
            $guessOutDir = $modelsDir;
            $parts = explode('\\', $modelName);
            $lastPart = array_pop($parts);
            $guessOutDir .= '/'.$lastPart;

            $outDir = $io->ask("The directory where we should save the Entity.php and Manager.php ", $guessOutDir);
            $ok = $io->confirm("The files will be:\n   ".$outDir."/Entity.php\n   ".$outDir."/Manager.php\n Ok? [Y=ok, N=type again] ", true);

            if($ok) {
                if (file_exists($outDir . '/Entity.php') || file_exists($outDir . '/Manager.php')) {
                    $ok = $io->confirm("Those files already exist. Do you want to overwrite them? [Y=overwrite, N=type again] ", false);
                }
            }
        } while(!$ok);

        if(!is_dir($outDir)) {
            mkdir($outDir, 0775, true);
        }

        //
        // Entity class
        $entityClassSrc = file_get_contents($templatesDir.'/EntityClass.tpl');
        if(empty($entityClassSrc)) {
            $io->error('ERROR: No tpl file: '.$templatesDir.'/EntityClass.tpl');
            return Command::FAILURE;
        }

        $entityClassSrc = str_replace('_NAMESPACE_', $namespace, $entityClassSrc);
        $entityClassSrc = str_replace('_ENT_CLASS_USES_', $this->buildUses(), $entityClassSrc);
        if(!empty($attributeDefs)) $attributeDefs = "    protected function relations(): ?array { return [\n".$attributeDefs."    ];}\n\n";
        $entityClassSrc = str_replace('_ATTRIBUTE_DEFS_', $attributeDefs, $entityClassSrc);
        $entityClassSrc = str_replace('_ACESSOR_METHODS_', $accessorMethodsSrc, $entityClassSrc);

        file_put_contents($outDir.'/Entity.php', $entityClassSrc);

        //
        // Manager class
        $managerClassSrc = file_get_contents($templatesDir.'/ManagerClass.tpl');
        if(empty($managerClassSrc)) {
            $io->error('ERROR: No tpl file: '.$templatesDir.'/ManagerClass.tpl');
            return Command::FAILURE;
        }

        $managerClassSrc = str_replace('_NAMESPACE_', $namespace, $managerClassSrc);
        $managerClassSrc = str_replace('_ENTITY_NAME_', $modelName, $managerClassSrc);
        $managerClassSrc = str_replace('_DB_ENV_NAME_', $dbSelect, $managerClassSrc);
        $managerClassSrc = str_replace('_DB_TBL_NAME_', $tbl, $managerClassSrc);
        $managerClassSrc = str_replace('_FIELD_TYPES_', $mgrFldTypes, $managerClassSrc);
        $managerClassSrc = str_replace('_FIELD_TOPOGEO_TYPES_', $mgrTopogeoFldTypes, $managerClassSrc);
        $managerClassSrc = str_replace('_DEFAULT_VALUES_', $mgrDefVals, $managerClassSrc);
        file_put_contents($outDir.'/Manager.php', $managerClassSrc);

        return Command::SUCCESS;
    }

    private function addUses(string $addThis): void
    {
        $addThis = trim($addThis, ";\n\t\r,\\ ");
        if(!in_array($addThis, $this->uses)) {
            $this->uses[] = $addThis;
        }
    }

    private function buildUses(): string
    {
        $str = '';
        foreach($this->uses as $use) {
            $str .= "use ".$use.";";
        }
        if(!empty($str)) {
            $str .= "\n";
        }
        return $str;
    }
}
