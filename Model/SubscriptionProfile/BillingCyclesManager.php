<?php
namespace TNW\Subscriptions\Model\SubscriptionProfile;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Quote\Api\CartRepositoryInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileInterface;
use TNW\Subscriptions\Api\Data\SubscriptionProfileOrderInterface;
use TNW\Subscriptions\Model\Config;
use TNW\Subscriptions\Model\Config\Source\BillingFrequencyUnitType;
use TNW\Subscriptions\Model\Context;
use TNW\Subscriptions\Model\ResourceModel\SubscriptionProfile\CollectionFactory;
use TNW\Subscriptions\Model\SubscriptionProfileOrder\Manager as RelationManager;
use TNW\Subscriptions\Model\SubscriptionProfileRepository;
use TNW\Subscriptions\Model\Config\Source\StartDateType;

/**
 * Class BillingCyclesManager
 * @package TNW\Subscriptions\Model\SubscriptionProfile
 */
class BillingCyclesManager
{
    /**
     * Repository for retrieving subscription profiles.
     *
     * @var SubscriptionProfileRepository
     */
    protected $profileRepository;

    /**
     * Search criteria builder.
     *
     * @var SearchCriteriaBuilder
     */
    protected $criteriaBuilder;

    /**
     * Subscriptions context.
     *
     * @var Context
     */
    protected $context;

    /**
     * Subscriptions config.
     *
     * @var Config
     */
    protected $config;

    /**
     * Repository fore saving/retrieving quotes.
     *
     * @var CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * Factory for creating subscription collection.
     *
     * @var CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Profile relation manager.
     *
     * @var RelationManager
     */
    protected $relationManager;

    /**
     * BillingCyclesManager constructor.
     * @param SubscriptionProfileRepository $profileRepository
     * @param SearchCriteriaBuilder $criteriaBuilder
     * @param Context $context
     * @param Config $config
     * @param CartRepositoryInterface $cartRepository
     * @param CollectionFactory $collectionFactory
     * @param RelationManager $relationManager
     */
    public function __construct(
        SubscriptionProfileRepository $profileRepository,
        SearchCriteriaBuilder $criteriaBuilder,
        Context $context,
        Config $config,
        CartRepositoryInterface $cartRepository,
        CollectionFactory $collectionFactory,
        RelationManager $relationManager
    ) {
        $this->profileRepository = $profileRepository;
        $this->criteriaBuilder = $criteriaBuilder;
        $this->context = $context;
        $this->config = $config;
        $this->cartRepository = $cartRepository;
        $this->collectionFactory = $collectionFactory;
        $this->relationManager = $relationManager;
    }

    /**
     * @param SubscriptionProfileInterface $profile
     * @param int $count
     * @param bool $existingCycles
     * @return array
     * @throws \Exception
     */
    public function getBillingCycles(SubscriptionProfileInterface $profile, $count = 0, $existingCycles = false)
    {
        $neededDates = [];
        $nowDate = new \DateTime();
        $formattedNowDate = $this->format($nowDate);
        $startDate = new \DateTime($profile->getStartDate());
        $startDay = $startDate->format('j');
        $formattedStartDate = $this->format($startDate);
        //Add to list start date.
        $neededDates[] = $formattedStartDate;
        //Profile has a infinite count of cycles
        if ($profile->getTerm()) {
            //Generate quotes for the year ahead
            $endDate = new \DateTime($formattedNowDate);
            if (strtotime($formattedStartDate) > strtotime($formattedNowDate)) {
                $endDate = new \DateTime($formattedStartDate);
            }
            $endDate->add(new \DateInterval('P1Y'));
            $dateDiff = $startDate->diff($endDate, true);
            switch ($profile->getUnit()) {
                case BillingFrequencyUnitType::DAYS:
                    $cyclesCount = floor($dateDiff->days / $profile->getFrequency());
                    break;
                case BillingFrequencyUnitType::MONTHS:
                    $months = $dateDiff->y * 12 + $dateDiff->m;
                    $cyclesCount = floor($months / $profile->getFrequency());
                    break;
                default:
                    throw new \Exception('Undefined length unit type.');
            }
        } else {
            // Profile has a finite count of cycles
            $cyclesCount = (int)$profile->getTotalBillingCycles() - 1;
        }

        $products = $profile->getProducts();
        $product = array_shift($products);
        //Calculate the list of dates for profile
        for ($i = 1; $i <= $cyclesCount; $i++) {
             $date = $this->calculateScheduledDate(
                $startDate,
                $profile->getUnit(),
                $profile->getFrequency(),
                $product->getMagentoProduct()->getData('tnw_subscr_start_date'),
                $startDay
            );
            $neededDates[] = $this->format($date);
        }
        //get already generated dates
        $existDates = array_map(
            function (SubscriptionProfileOrderInterface $relation) {
                return $relation->getScheduledAt();
            },
            $this->relationManager->getAllProfileRelations($profile->getId())
        );
        $neededDates = array_diff($neededDates, $existDates);
        //generate only future dates
        $resultDates = array_filter(
            $neededDates,
            function ($neededDate) use ($formattedNowDate) {
                $neededDateDayString = new \DateTime($neededDate);
                $formattedNowDateString = new \DateTime($formattedNowDate);
                return (strtotime($neededDateDayString->format('Y-m-d'))
                    > strtotime($formattedNowDateString->format('Y-m-d')));
            }
        );
        if ($count > 0) {
            $resultDates = array_slice($resultDates, 0, $count);
        }
        $needMore = count($neededDates) > count($resultDates);

        $result = [$resultDates, $needMore];
        if ($existingCycles) {
            array_push($result, $existDates);
        }

        return $result;
    }

    /**
     * Calculates subscription date for billing cycle.
     *
     * @param \DateTime $date
     * @param string $unit
     * @param int $length
     * @param string $startDateType
     * @param string $startDay
     * @return \DateTime
     * @throws \Exception
     */
    private function calculateScheduledDate(\DateTime $date, $unit, $length, $startDateType, $startDay)
    {
        switch ($unit) {
            case BillingFrequencyUnitType::DAYS:
                $intervalUnit = 'D';
                $expression = 'P' . $length . $intervalUnit;
                $date = $date->add(new \DateInterval($expression));
                break;
            case BillingFrequencyUnitType::MONTHS:
                $intervalUnit = 'M';
                $expression = 'P' . $length . $intervalUnit;
                $date->modify('first day of this month');
                $date = $date->add(new \DateInterval($expression));
                if ($startDateType == StartDateType::LAST_DAY_OF_THE_CURRENT_MONTH) {
                    $date->modify('last day of this month');
                    break;
                }
                if ($startDay > $date->format('t')) {
                    $startDay = $date->format('t');
                }
                $date->setDate($date->format('Y'), $date->format('n'), $startDay);
                break;
            default:
                throw new \Exception('Undefined length unit type.');
        }

        return $date;
    }

    /**
     * Returns formatted date.
     *
     * @param \DateTime $date
     * @return string
     */
    private function format(\DateTime $date)
    {
        return $date->format(\Magento\Framework\Stdlib\DateTime::DATETIME_PHP_FORMAT);
    }
}
