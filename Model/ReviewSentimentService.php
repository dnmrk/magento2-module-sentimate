<?php
/**
 *  Copyright © Above The Fray Design, Inc. All rights reserved.
 *  See ATF_COPYING.txt for license details.
 **/

declare(strict_types=1);

namespace Macademy\Sentimate\Model;

use Exception;
use Magento\Framework\Exception\NoSuchEntityException;
use Psr\Log\LoggerInterface;

class ReviewSentimentService
{
    /**
     * CONSTRUCTOR
     *
     * @param ResourceModel\ReviewSentiment $reviewSentimentResourceModel
     * @param LoggerInterface $logger
     * @param ReviewSentimentFactory $reviewSentimentFactory
     */
    public function __construct(
        private readonly ResourceModel\ReviewSentiment $reviewSentimentResourceModel,
        private readonly LoggerInterface               $logger,
        private readonly ReviewSentimentFactory        $reviewSentimentFactory,
    ) {
    }

    /**
     * Save
     *
     * @param ReviewSentiment $reviewSentiment
     * @return void
     */
    public function save(
        ReviewSentiment $reviewSentiment
    ): void {
        try {
            $this->reviewSentimentResourceModel->save($reviewSentiment);
        } catch (Exception $e) {
            $this->logger->error(__('Failed to save sentiment analysis: %1', $e->getMessage()));
        }
    }

    /**
     * GetByReviewId
     *
     * @param int $reviewId
     * @return ReviewSentiment
     * @throws NoSuchEntityException
     */
    public function getByReviewId(
        int $reviewId
    ): ReviewSentiment {
        $reviewSentiment = $this->reviewSentimentFactory->create();
        $this->reviewSentimentResourceModel->load($reviewSentiment, $reviewId, 'review_id');

        if (!$reviewSentiment->getId()) {
            throw new NoSuchEntityException(
                __('THe review sentiment with %1 review ID does not exist.', $reviewId)
            );
        }
        return $reviewSentiment;
    }
}
