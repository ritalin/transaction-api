<?php

namespace TransactionApi;

interface TransactionInterface
{
    /**
     * Begin transaction.
     */
    public function begin();
    
    /**
     * Commit current transaction.
     */
    public function commit();
    
    /**
     * Rollback current transaction.
     */
    public function rollback();
    
    /**
     * Check wheather inside transaction scope.
     */
    public function inTransaction();
    
    /**
     * Get nest level of current transaction.
     */
    public function depth();
}
