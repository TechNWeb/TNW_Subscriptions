<?php
/**
 * Copyright © 2018 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Subscriptions\Ui\Component\Listing\Column\Report;

use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\BooleanUtils;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Listing\Columns\Date as OriginDate;
use Magento\Framework\App\RequestInterface;

/**
 * Class Date - report date column component
 */
class Date extends OriginDate
{
    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var BooleanUtils
     */
    private $booleanUtils;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * Date constructor.
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param TimezoneInterface $timezone
     * @param BooleanUtils $booleanUtils
     * @param RequestInterface $request
     * @param array $components
     * @param array $data
     * @param ResolverInterface|null $localeResolver
     * @param DataBundle|null $dataBundle
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        TimezoneInterface $timezone,
        BooleanUtils $booleanUtils,
        RequestInterface $request,
        array $components = [],
        array $data = [],
        ?ResolverInterface $localeResolver = null,
        ?DataBundle $dataBundle = null
    ) {
        $this->request = $request;
        $this->timezone = $timezone;
        $this->booleanUtils = $booleanUtils;
        parent::__construct(
            $context,
            $uiComponentFactory,
            $timezone,
            $booleanUtils,
            $components,
            $data,
            $localeResolver,
            $dataBundle
        );
    }

    /**
     * @inheritdoc
     */
    public function prepareDataSource(array $dataSource)
    {
        $period = $this->request->getParam('period');

        if (isset($dataSource['data']['items']) && $period !== 'year') {
            foreach ($dataSource['data']['items'] as & $item) {
                $name = $this->getData('name');
                if (isset($item[$name])
                    && $item[$name] !== "0000-00-00 00:00:00"
                ) {
                    $date = $this->timezone->date(new \DateTime($item[$name]));
                    $timezone = isset($this->getConfiguration()['timezone'])
                        ? $this->booleanUtils->convert($this->getConfiguration()['timezone'])
                        : true;
                    if (!$timezone) {
                        $date = new \DateTime($item[$name]);
                    }
                    switch ($period) {
                        case 'month':
                            $format= 'Y-m';
                            break;
                        default:
                            $format= 'Y-m-d';
                            break;
                    }
                    $item[$name] = $date->format($format);
                }
            }
        }

        return $dataSource;
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        parent::prepare();
        $config = $this->getData('config');
        $period = $this->request->getParam('period');
        switch ($period) {
            case 'month':
                $config['dateFormat'] = 'MM/yy';
                break;
            case 'year':
                $config['dateFormat'] = 'yy';
                break;
            default:
                $config['dateFormat'] = 'MM/d/yy';
                break;
        }
        $this->setData('config', $config);
    }
}
