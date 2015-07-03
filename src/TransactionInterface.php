<?php

namespace TransactionApi;

interface TransactionInterface
{
    public function begin();
    public function commit();
    public function rollback();
    public function inTransaction();
    public function depth();
}
