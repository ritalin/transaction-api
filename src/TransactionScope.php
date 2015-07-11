<?php

namespace TransactionApi;

use TransactionApi\Annotation\Transactional;

class TransactionScope
{
    /**
     * If transaction is not started, new transaction must be started.
     * If called inside transaction scope, current transaction must be continued.
     */
    const REQUIRES = 1;
    /**
     * Nested transaction support.
     * If transaction is not started, new transaction must be started.
     * If called inside transaction scope, current transaction must be suspend, then new transaction must be started.
     */
    const REQUIRES_NEW = 2;
    /**
     * If transaction is not started, new transaction must be started.
     * If called inside transaction scope, InvalidTransactionException must be thrown.
     */
    const REQUIRES_ONE = 3;

    public function __construct(TransactionInterface $tran, Transactional $annotation)
    {
        $this->tran = $tran;
        $this->txType = $annotation->txType;
    }

    /**
     * @param callable fn ()->mixed
     */
    public function runInto(callable $fn)
    {
        $started = $this->tryBegin();
        $saveDepth = $this->tran->depth();
        try {
            $result = $fn();

            $actualDepth = $this->tran->depth();

            if ($saveDepth !== $actualDepth) {
                throw new InvalidTransactionException(
                    "Transaction nest level is not matched (expected: $saveDepth, actual: $actualDepth)", -1001
                );
            }

            if ($started) {
                $this->tran->commit();
            }

            return $result;
        }
        catch (\Exception $ex) {
            if ($ex instanceof InvalidTransactionException) {
                throw $ex;
            }

            $actualDepth = $this->tran->depth();

            if ($saveDepth !== $actualDepth) {
                throw new InvalidTransactionException(
                    "Transaction nest level is not matched (expected: $saveDepth, actual: $actualDepth)", -1001
                );
            }

            if ($started) {
                $this->tran->rollback();
            }

            throw $ex;
        }
    }

    private function tryBegin()
    {
        if (! $this->tran->inTransaction()) {
            $this->tran->begin();
            return true;
        }
        else {
            switch ($this->txType) {
            case self::REQUIRES: return false;
            case self::REQUIRES_NEW: {
                $this->tran->begin();
                return true;
            }
            case self::REQUIRES_ONE:
                throw new InvalidTransactionException('Transaction has already been started.', -1002);
            default:
                throw new InvalidTransactionException('Unknown transaction type', -1003);
            }
        }
    }
}
