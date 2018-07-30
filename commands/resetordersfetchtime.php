<?php
/**
 * Created by PhpStorm.
 * User: droid
 * Date: 1/1/18
 * Time: 11:07 PM
 */

namespace resgef\synclist\commands\resetordersfetchtime;

use Carbon\Carbon;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ResetOrdersFetchTimeCommand extends Command
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
        $this->setName('cron:reset_orders_fetchtime')
            ->setDescription("reset the orders save_time to specified days or months ago. Saves your ass when cron messed up and some orders were not fetched")
            ->addArgument("days_or_hours_ago", InputArgument::REQUIRED, "the number of days or hours to go behind")
            ->addArgument("remote_provider", InputArgument::REQUIRED, "the remote provider like ebay, etsy")
            ->addArgument("account_name", InputArgument::REQUIRED, "the provider api key account_name as saved in synclist database")
            ->setHelp("")
            ->addUsage("{$this->getName()} ebay hanksminerale 4days|5hours");
    }

    function execute(InputInterface $input, OutputInterface $output)
    {
        $days_or_hours_ago = $input->getArgument('days_or_hours_ago');
        $number = (int)substr($days_or_hours_ago, 0, 1);
        if (!$number) {
            $output->writeln("<error>provided `{$days_or_hours_ago}` does not start with a number</error>");
            return 1;
        }
        $remote_provider = $input->getArgument('remote_provider');
        $account_name = $input->getArgument("account_name");
        if (!$this->registry->db->query("select * from sl_orders_last_fetch_time where remote_provider='$remote_provider' AND account_name='$account_name'")->num_rows) {
            $output->writeln("<error>no row in database for remote_provider:$remote_provider and account_name:$account_name</error>");
            return 2;
        }

        $current_fetchtime = $this->registry->db->query("select * from sl_orders_last_fetch_time where remote_provider='$remote_provider' AND account_name='$account_name'")->row['fetchtime'];
        $output->writeln("current last fetch time is {$current_fetchtime} which is " . Carbon::createFromFormat($this->registry->config->get('mysql_datetime_format'), $current_fetchtime)->diffForHumans(Carbon::now()));

        if (strpos($days_or_hours_ago, 'hours', 1) !== false) {
            $datetime = Carbon::now()->subHours($number)->format($this->registry->config->get('mysql_datetime_format'));
            $output->writeln("will set orders fetch time to $number hours ago to $datetime");
        } elseif (strpos($days_or_hours_ago, 'days', 1) !== false) {
            $datetime = Carbon::now()->subDays($number)->format($this->registry->config->get('mysql_datetime_format'));
            $output->writeln("will set orders fetch time to $number days ago to $datetime");
        } else {
            $output->writeln("<error>no time specifier found in '{$days_or_hours_ago}'</error>");
            return 3;
        }
        $this->registry->db->query("update sl_orders_last_fetch_time set fetchtime='$datetime' where remote_provider='$remote_provider' AND account_name='$account_name'");
        return 0;
    }
}