<?php

namespace TransactionApi;

use TransactionApi\Annotation\Transactional;
use TransactionApi\InvalidTransactionException;

class TransactionStub implements TransactionInterface
{
    /**
     * @var integer
     */
    private $depth = 0;

    public function begin()
    {
        ++$this->depth;
    }

    public function commit()
    {
        --$this->depth;
    }

    public function rollback()
    {
        --$this->depth;
    }

    public function inTransaction()
    {
        return $this->depth() > 0;
    }

    public function depth()
    {
        return $this->depth;
    }
}

class TransactionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function test_transaction_scope()
    {
        $tran = new TransactionStub();

        $scope = new TransactionScope($tran, new Transactional());

        $this->assertEquals(0, $tran->depth());

        $scope->runInto(function () use ($tran) {
            $this->assertEquals(1, $tran->depth());
        });

        $this->assertEquals(0, $tran->depth());
    }

    /**
     * @test
     * @expectedException \LogicException
     * @expectedExceptionMessage error occured
     */
    public function test_to_rollback_transaction_scope()
    {
        $tran = new TransactionStub();
        $scope = new TransactionScope($tran, new Transactional());

        $this->assertEquals(0, $tran->depth());

        try {
            $scope->runInto(function () use ($tran) {
                $this->assertEquals(1, $tran->depth());

                throw new \LogicException('error occured');
            });

            $this->fail('must not reach');
        } catch (\Exception $ex) {
            $this->assertEquals(0, $tran->depth());

            throw $ex;
        }
    }

    /**
     * @test
     * @expectedException \TransactionApi\InvalidTransactionException
     * @expectedExceptionCode -1001
     */
    public function test_using_invalid_commit()
    {
        $tran = new TransactionStub();

        $scope = new TransactionScope($tran, new Transactional());

        $this->assertEquals(0, $tran->depth());

        $scope->runInto(function () use ($tran) {
            $tran->commit();

            $this->assertEquals(0, $tran->depth());
        });

        $this->fail('must not reach');
    }

    /**
     * @test
     * @expectedException \TransactionApi\InvalidTransactionException
     * @expectedExceptionCode -1001
     */
    public function test_using_invalid_rollback()
    {
        $tran = new TransactionStub();
        $scope = new TransactionScope($tran, new Transactional());

        $this->assertEquals(0, $tran->depth());

        $scope->runInto(function () use ($tran) {
            $tran->rollback();

            $this->assertEquals(0, $tran->depth());
        });

        $this->fail('must not reach');
    }

    /**
     * @test
     * @expectedException \TransactionApi\InvalidTransactionException
     * @expectedExceptionCode -1002
     */
    public function test_restrict_nesting_transaction()
    {
        $tran = new TransactionStub();
        $tran->begin();

        $scope = new TransactionScope($tran, new Transactional());

        $this->assertEquals(1, $tran->depth());

        $scope->runInto(function () use ($tran) {
            $this->fail('must not reach');
        });

        $this->fail('must not reach');
    }

    /**
     * @test
     */
    public function test_ignoring_nested_transaction()
    {
        $tran = new TransactionStub();
        $tran->begin();

        $annotation = new Transactional();
        $annotation->txType = TransactionScope::REQUIRES;

        $scope = new TransactionScope($tran, $annotation);

        $this->assertEquals(1, $tran->depth());

        $scope->runInto(function () use ($tran) {
            $this->assertEquals(1, $tran->depth());
        });

        $this->assertEquals(1, $tran->depth());
    }

    /**
     * @test
     * @expectedException \TransactionApi\InvalidTransactionException
     * @expectedExceptionCode -1003
     */
    public function test_using_invalid_tx_type()
    {
        $tran = new TransactionStub();
        $tran->begin();

        $annotation = new Transactional();
        $annotation->txType = 9999;

        $scope = new TransactionScope($tran, $annotation);

        $this->assertEquals(1, $tran->depth());

        $scope->runInto(function () use ($tran) {
            $this->fail('must not reach inside anonymous function');
        });

        $this->fail('must not reach');
    }

    /**
     * @test
     */
    public function test_using_nested_transaction()
    {
        $tran = new TransactionStub();
        $tran->begin();

        $annotation = new Transactional();
        $annotation->txType = TransactionScope::REQUIRES_NEW;

        $scope = new TransactionScope($tran, $annotation);

        $this->assertEquals(1, $tran->depth());

        $scope->runInto(function () use ($tran) {
            $this->assertEquals(2, $tran->depth());
        });

        $this->assertEquals(1, $tran->depth());
    }
}
