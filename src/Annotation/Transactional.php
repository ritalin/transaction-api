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
     * @var int
     *
     * @see TransactionScope
     */
    public $txType = TransactionScope::REQUIRES_ONE;
}
