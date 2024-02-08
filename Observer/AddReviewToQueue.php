<?php declare(strict_types=1);
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 **/

namespace Macademy\Sentimate\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\MessageQueue\PublisherInterface;
use Magento\Framework\Serialize\SerializerInterface;

class AddReviewToQueue implements ObserverInterface
{

    /**
     * @param PublisherInterface $publisher
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly PublisherInterface $publisher,
        private readonly SerializerInterface $serializer
    ) {
    }

    /**
     * Adds a review to the queue
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(
        Observer $observer
    ): void {
        $review = $observer->getEvent()->getData('object');

        if ($review->isObjectNew()) {
            // Add logic to push message
            $reviewData = $review->getData();
            $serializedReviewData = $this->serializer->serialize($reviewData);
            $this->publisher->publish('macademy.sentimate.reviews', $serializedReviewData);
        }
    }
}
