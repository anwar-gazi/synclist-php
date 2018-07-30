<?php

namespace Resgef\SyncList\Commands\PhinxLogRemove;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PhinxLogRemoveCommand extends Command
{
    /** @var \Registry $registry */
    private $registry;

    function __construct(\Registry $registry)
    {
        parent::__construct(null);
        $this->registry = $registry;
    }

    protected function configure()
    {
        $this->setName('phinx:removelog')->setDescription('clear a phinx migration version log form database so you can run it again')->addArgument('version', InputArgument::REQUIRED, 'the migration version or portion of a name of the migration file');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->registry->load->model('migrations/phinx');
        /** @var \Modelmigrationsphinx $model */
        $model = $this->registry->get('model_migrations_phinx');
        $needle = $input->getArgument('version');
        $migration_dir = dirname(__DIR__) . '/db/migrations/';
        $Dir = new \DirectoryIterator($migration_dir);
        foreach ($Dir as $item) {
            if ($item->isFile() && ($item->getExtension() == 'php')) {
                $filename = $item->getBasename();
                if (strpos($filename, $needle) !== false) {
                    $parts = explode('_', $filename);
                    $version = reset($parts);
                    if ($model->remove_phinxlog($version)) {
                        $output->writeln("phinxlog for version:$version filename=$filename removed!");
                    }
                }
            }
        }
    }
}