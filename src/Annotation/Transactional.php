<?php

namespace TransactionApi\Annotation;

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
    public $txType;
}
