<?php

namespace Resgef\SyncList\Commands\CurrentVersion;

use resgef\synclist\system\helper\versioning\Versioning;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CurrentVersionCommand extends Command
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
        $this->setName("version:current")
            ->addOption('now', null, InputArgument::OPTIONAL, 'build the version number from current time instead of git commit, this is usefull to avoid extra committing to include the generated VERSION file')
            ->setDescription("build current version number from current time(preferred) or last git commit date and write the version number and last commit timestamp in filesystem");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo_path = ROOT_DIR;
        $version_file = $this->regsitry->config->get('version_file');
        $from_current_time = $input->getOption('now');
        if ($from_current_time) {
            $output->writeln("building from current timestamp");
            $last_commit_timestamp = time();
            $version = Versioning::get_version_from_timestamp($last_commit_timestamp);
        } else {
            $output->writeln('building from git last commit timestamp');
            $last_commit_timestamp = Versioning::get_git_last_commit_timestamp($repo_path);
            $version = Versioning::get_version_from_timestamp($last_commit_timestamp);
        }

        Versioning::write_version_file($version_file, $version, $last_commit_timestamp);
        $output->writeln("$version");
        $output->writeln("version written in $version_file");
    }
}