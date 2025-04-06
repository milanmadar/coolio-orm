<?php

namespace Milanmadar\CoolioORM;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;

class ScaffoldCommand extends Command
{
    private ORM $orm;

    public function __construct(ORM $orm)
    {
        $this->orm = $orm;

        // Keep this at the end of this constructor
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('app:scaffold');
        $this->setDescription('Scaffolds Entity and Manager classes from your database');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

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

        // table
        do {
            $tables = $sm->listTableNames();
            $tbl = $io->choice('Table', $tables);

            $cols = $sm->listTableColumns($tbl);
            if (empty($cols)) {
                $io->error('ERROR: No such table in the selected database. Try again');
            }
        } while(empty($cols));

        // Model name
        do {
            // namespace
            $ns = $io->ask("What is the namespace prefix for all your Models? (like 'App\Model\\')", 'App\Model\\');
            $ns = str_replace("/", "\\", $ns);
            $ns = trim($ns, "\\");

            // entity name
            $entityName = $io->ask("Name of you new Model name (without without the namespace and without '\Entity', for example just 'Product')");
            $entityName = str_replace("/", "\\", $entityName);
            $entityName = trim($entityName, "\\");
            if (str_starts_with($entityName, "App\\")) $entityName = substr($entityName, 4);
            if (str_starts_with($entityName, "Model\\")) $entityName = substr($entityName, 6);
            $entityNameFull = $ns.'\\'.$entityName.'\Entity';

            // Ask user confirmation
            $ok = $io->confirm('So your Entity will be '.$entityNameFull.'. Ok?', true);
        } while(!$ok);

        return Command::SUCCESS;
    }
}
