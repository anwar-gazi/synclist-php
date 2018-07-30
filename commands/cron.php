<?php

namespace resgef\synclist\commands\Cron;

use Carbon\Carbon;
use Resgef\Synclist\System\Helper\ServerTime\ServerTime;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends Command
{
    /** @var \Registry $registry */
    private $registry;

    function __construct(\Registry $registry)
    {
        parent::__construct(null);
        $this->registry = $registry;
    }

    function configure()
    {
        $this->setName('cron:execute')
            ->setDescription('execute a cron')
            ->addArgument('index', InputArgument::OPTIONAL, 'the cron index(starting from zero) in the cron config')
            ->addOption('force', 'f', null, 'run the cron forcefully');
    }

    function execute(InputInterface $input, OutputInterface $output)
    {
        $index = $input->getArgument('index');
        $cron_config = $this->registry->config->get('cron');
        if ($cron_config['disable_all']) {
            $output->writeln("cant run any cron, all are disabled!");
            return 1;
        }
        if ($index === null) {
            $cron_list = [];
            foreach ($cron_config['crons'] as $i => $cron_entry) {
                $cron_list[] = "$i# {$cron_entry['class_name']} " . ($cron_entry['enabled'] ? '' : 'disabled');
            }
            $cron_list = implode("\n", $cron_list);
            $output->writeln("select an index");
            $output->writeln($cron_list);
            return 0;
        }

        # index given
        $cron_entry = $cron_config['crons'][$index];

        if (!$cron_entry['enabled']) { //is disabled?
            if ($input->getOption('force')) {
                $output->writeln('cron disabled! running forcefully!');
            } else {
                $output->writeln("cannot run: this cron is disabled! {$cron_entry['class_name']}");
                return 0;
            }
        }

        #cron okay to execute
        $output->writeln("<info>{$index}# {$cron_entry['class_name']}</info>");
        $cronlog_file = join_path(DIR_LOGS, $this->registry->config->get('cron_error_log'));
        file_put_contents($cronlog_file, '');

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        ini_set('log_errors', 1);
        ini_set('error_log', $cronlog_file);

        $start = Carbon::now();

        /** @var \resgef\synclist\system\interfaces\croninterface\CronInterface $controller */
        $controller = new $cron_entry['class_name']($this->registry);
        $controller->execute();

        $cron_dur = ServerTime::diffHumanReadable($start);
        $output->writeln("finished in $cron_dur");

        return 0;
    }
}