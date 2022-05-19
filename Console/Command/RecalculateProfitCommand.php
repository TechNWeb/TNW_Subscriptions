<?php
/**
 * Copyright © 2021 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Subscriptions\Console\Command;

use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TNW\Subscriptions\Api\SubscriptionProfileRepositoryInterface;
use TNW\Subscriptions\Model\Queue\Profit;

class RecalculateProfitCommand extends Command
{
    const INPUT_KEY_TYPES = 'profile_id';

    /**
     * @var SubscriptionProfileRepositoryInterface
     */
    private $subscriptionProfileRepository;

    /**
     * @var Profit
     */
    private $profitModel;

    /**
     * @param SubscriptionProfileRepositoryInterface $subscriptionProfileRepository
     * @param Profit $profitModel
     */
    public function __construct(
        SubscriptionProfileRepositoryInterface $subscriptionProfileRepository,
        Profit $profitModel
    ) {
        $this->subscriptionProfileRepository = $subscriptionProfileRepository;
        $this->profitModel = $profitModel;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->addArgument(
            self::INPUT_KEY_TYPES,
            InputArgument::REQUIRED,
            'Subscription Profile ID'
        );
        $this->setName('tnw_subscriptions:profit');
        $this->setDescription('Recalculates profit for specific profile');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profileId = $input->getArgument(self::INPUT_KEY_TYPES);
        try {
            $profile = $this->subscriptionProfileRepository->getById($profileId);
        } catch (NoSuchEntityException $e) {
            throw new \InvalidArgumentException("There is no Subscription Profile with ID=" . $profileId);
        }
        try {
            $this->profitModel->calculateProfitAndSave($profile);
        } catch (LocalizedException $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");
            // we must have an exit code higher than zero to indicate something was wrong
            return Cli::RETURN_FAILURE;
        }
        $output->write("\n");
        $output->writeln("<info>Profit Successfully Recalculated for Profile " . $profile->getLabel() . "</info>");
        return Cli::RETURN_SUCCESS;
    }
}
