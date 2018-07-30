<?php

namespace resgef\synclist\commands\getapiaccessrules;

use resgef\synclist\system\datatypes\ebayapikeysmodel\EbayApiKeysModel;
use Resgef\SyncList\System\Library\EbayApi\EbayApiResponse\EbayApiResponse;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetApiAccessRulesCommand extends Command
{
    private $registry;

    function __construct(\Registry $registry)
    {
        parent::__construct(null);
        $this->registry = $registry;
    }

    function configure()
    {
        $this->setName('ebay:api_access_rules')->setDescription('to report on how many Trading API calls your application has made and how many it is allowed to make. The call retrieves the access rules for various Trading API calls and shows how many calls your application has made in the past hour and past day.')
            ->addArgument('api_account_name', InputArgument::REQUIRED, 'the api key account name as save in local database');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     * @throws \resgef\synclist\system\exceptions\apikeynotfoundexception\ApiKeyNotFoundException
     * @throws \resgef\synclist\system\exceptions\notproperlyloaded\NotProperlyLoaded
     */
    function execute(InputInterface $input, OutputInterface $output)
    {
        $account_name = $input->getArgument('api_account_name');
        $api_key = new EbayApiKeysModel();
        $api_key->dependency_injection($this->registry);
        $api_key->load($account_name);
        /** @var EbayApiResponse $response */
        $response = $api_key->GetApiAccessRules();
        if ($response->error) {
            $output->writeln("<error>{$response->error}</error>");
        } else {
            $output->writeln("<info>{$response->xml->asXML()}</info>");
        }
    }
}