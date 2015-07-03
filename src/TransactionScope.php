<?php

namespace TransactionApi;

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
    
    public function __construct(TransactionInterface $tran, Transactional $annotation) {
        $this->tran = $tran;
        $This->txType = $annotation->txType;
    }
    
    public function runInto(callable $fn)
    {
        $this->tryBegin();
        try {
            $result = $fn();
            
            $this->tran->commit();
            
            return $result;
        }
        catch (\Except $ex) {
            $this->tran->rollback();
            
            throw $ex;
        }
    }
    
    private function tryBegin() {
        if (! $this->tran->inTransaction()) {
            $this->tran->begin();
        }
        else { 
            switch ($this->txType) {
            case REQUIRES: break;
            case REQUIRES_NEW: {
                $this->tran->begin();
                break;
            case REQUIRES_ONE: {
                throw new InvalidTransactionException('Transaction has already been started.');
            default:
                throw new InvalidTransactionException('Unknown transaction type');
            }
        }
    }
}