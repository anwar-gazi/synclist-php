<?php

namespace Resgef\SyncList\Commands\DeployPhinx;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;

class DeployPhinxCommand extends Command
{
    /** @var \Registry $regsitry */
    private $regsitry;

    function __construct(\Registry $registry)
    {
        parent::__construct(null);
        $this->regsitry = $registry;
    }

    protected function configure()
    {
        $this->setName("phinx:deploy")
            ->setDescription("set database config in phinx.yml");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = 'default';
        $phinx_config_file = __DIR__ . "/../phinx.yml";
        $db_creds = $this->regsitry->config->get('dbmysqli')[$name];
        $yml = new Parser();
        $phinxconf = $yml->parse(file_get_contents($phinx_config_file));
        $phinxconf['environments']['default_database'] = $name;
        $phinxconf['environments'][$name] = [
            'adapter' => 'mysql',
            'host' => $db_creds['hostname'],
            'name' => $db_creds['database'],
            'user' => $db_creds['username'],
            'pass' => $db_creds['password'],
            'port' => $db_creds['port'],
            'charset' => 'utf8',
        ];
        $dumper = new Dumper();
        file_put_contents($phinx_config_file, $dumper->dump($phinxconf));
        $output->writeln("phinx config $phinx_config_file fillup success!");
        return 0;
    }
}