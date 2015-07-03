<?php

namespace TransactionApi\Annotation;

use TransactionApi\TransactionScope;

/**
 * @Annotation
 * @Target("METHOD")
 */
class Transactional
{
    /**
     * @var integer
     *
     * @see TransactionScope
     */
    public $txType = TransactionScope::REQUIRES_ONE;
}
