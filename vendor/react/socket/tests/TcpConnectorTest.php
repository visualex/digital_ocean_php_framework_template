<?php

namespace React\Tests\Socket;

use Clue\React\Block;
use React\EventLoop\Factory;
use React\Socket\ConnectionInterface;
use React\Socket\TcpConnector;
use React\Socket\TcpServer;

class TcpConnectorTest extends TestCase
{
    const TIMEOUT = 0.1;

    /**
     * @test
     * @expectedException RuntimeException
     * @expectedExceptionMessage Connection to tcp://127.0.0.1:9999 failed: Connection refused
     */
    public function connectionToEmptyPortShouldFail()
    {
        $loop = Factory::create();

        $connector = new TcpConnector($loop);
        $promise = $connector->connect('127.0.0.1:9999');

        Block\await($promise, $loop, self::TIMEOUT);
    }

    /** @test */
    public function connectionToTcpServerShouldAddResourceToLoop()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new TcpConnector($loop);

        $server = new TcpServer(0, $loop);

        $valid = false;
        $loop->expects($this->once())->method('addWriteStream')->with($this->callback(function ($arg) use (&$valid) {
            $valid = is_resource($arg);
            return true;
        }));
        $connector->connect($server->getAddress());

        $this->assertTrue($valid);
    }

    /** @test */
    public function connectionToTcpServerShouldSucceed()
    {
        $loop = Factory::create();

        $server = new TcpServer(9999, $loop);
        $server->on('connection', $this->expectCallableOnce());
        $server->on('connection', array($server, 'close'));

        $connector = new TcpConnector($loop);

        $connection = Block\await($connector->connect('127.0.0.1:9999'), $loop, self::TIMEOUT);

        $this->assertInstanceOf('React\Socket\ConnectionInterface', $connection);

        $connection->close();
    }

    /** @test */
    public function connectionToTcpServerShouldSucceedWithRemoteAdressSameAsTarget()
    {
        $loop = Factory::create();

        $server = new TcpServer(9999, $loop);
        $server->on('connection', array($server, 'close'));

        $connector = new TcpConnector($loop);

        $connection = Block\await($connector->connect('127.0.0.1:9999'), $loop, self::TIMEOUT);
        /* @var $connection ConnectionInterface */

        $this->assertEquals('tcp://127.0.0.1:9999', $connection->getRemoteAddress());

        $connection->close();
    }

    /** @test */
    public function connectionToTcpServerShouldSucceedWithLocalAdressOnLocalhost()
    {
        $loop = Factory::create();

        $server = new TcpServer(9999, $loop);
        $server->on('connection', array($server, 'close'));

        $connector = new TcpConnector($loop);

        $connection = Block\await($connector->connect('127.0.0.1:9999'), $loop, self::TIMEOUT);
        /* @var $connection ConnectionInterface */

        $this->assertContains('tcp://127.0.0.1:', $connection->getLocalAddress());
        $this->assertNotEquals('tcp://127.0.0.1:9999', $connection->getLocalAddress());

        $connection->close();
    }

    /** @test */
    public function connectionToTcpServerShouldSucceedWithNullAddressesAfterConnectionClosed()
    {
        $loop = Factory::create();

        $server = new TcpServer(9999, $loop);
        $server->on('connection', array($server, 'close'));

        $connector = new TcpConnector($loop);

        $connection = Block\await($connector->connect('127.0.0.1:9999'), $loop, self::TIMEOUT);
        /* @var $connection ConnectionInterface */

        $connection->close();

        $this->assertNull($connection->getRemoteAddress());
        $this->assertNull($connection->getLocalAddress());
    }

    /** @test */
    public function connectionToTcpServerWillCloseWhenOtherSideCloses()
    {
        $loop = Factory::create();

        // immediately close connection and server once connection is in
        $server = new TcpServer(0, $loop);
        $server->on('connection', function (ConnectionInterface $conn) use ($server) {
            $conn->close();
            $server->close();
        });

        $once = $this->expectCallableOnce();
        $connector = new TcpConnector($loop);
        $connector->connect($server->getAddress())->then(function (ConnectionInterface $conn) use ($once) {
            $conn->write('hello');
            $conn->on('close', $once);
        });

        $loop->run();
    }

    /** @test */
    public function connectionToEmptyIp6PortShouldFail()
    {
        $loop = Factory::create();

        $connector = new TcpConnector($loop);
        $connector
            ->connect('[::1]:9999')
            ->then($this->expectCallableNever(), $this->expectCallableOnce());

        $loop->run();
    }

    /** @test */
    public function connectionToIp6TcpServerShouldSucceed()
    {
        $loop = Factory::create();

        try {
            $server = new TcpServer('[::1]:9999', $loop);
        } catch (\Exception $e) {
            $this->markTestSkipped('Unable to start IPv6 server socket (IPv6 not supported on this system?)');
        }

        $server->on('connection', $this->expectCallableOnce());
        $server->on('connection', array($server, 'close'));

        $connector = new TcpConnector($loop);

        $connection = Block\await($connector->connect('[::1]:9999'), $loop, self::TIMEOUT);
        /* @var $connection ConnectionInterface */

        $this->assertEquals('tcp://[::1]:9999', $connection->getRemoteAddress());

        $this->assertContains('tcp://[::1]:', $connection->getLocalAddress());
        $this->assertNotEquals('tcp://[::1]:9999', $connection->getLocalAddress());

        $connection->close();
    }

    /** @test */
    public function connectionToHostnameShouldFailImmediately()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();

        $connector = new TcpConnector($loop);
        $connector->connect('www.google.com:80')->then(
            $this->expectCallableNever(),
            $this->expectCallableOnce()
        );
    }

    /** @test */
    public function connectionToInvalidPortShouldFailImmediately()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();

        $connector = new TcpConnector($loop);
        $connector->connect('255.255.255.255:12345678')->then(
            $this->expectCallableNever(),
            $this->expectCallableOnce()
        );
    }

    /** @test */
    public function connectionToInvalidSchemeShouldFailImmediately()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();

        $connector = new TcpConnector($loop);
        $connector->connect('tls://google.com:443')->then(
            $this->expectCallableNever(),
            $this->expectCallableOnce()
        );
    }

    /** @test */
    public function cancellingConnectionShouldRemoveResourceFromLoopAndCloseResource()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new TcpConnector($loop);

        $server = new TcpServer(0, $loop);
        $server->on('connection', $this->expectCallableNever());

        $loop->expects($this->once())->method('addWriteStream');
        $promise = $connector->connect($server->getAddress());

        $resource = null;
        $valid = false;
        $loop->expects($this->once())->method('removeWriteStream')->with($this->callback(function ($arg) use (&$resource, &$valid) {
            $resource = $arg;
            $valid = is_resource($arg);
            return true;
        }));
        $promise->cancel();

        // ensure that this was a valid resource during the removeWriteStream() call
        $this->assertTrue($valid);

        // ensure that this resource should now be closed after the cancel() call
        $this->assertInternalType('resource', $resource);
        $this->assertFalse(is_resource($resource));
    }

    /** @test */
    public function cancellingConnectionShouldRejectPromise()
    {
        $loop = Factory::create();
        $connector = new TcpConnector($loop);

        $server = new TcpServer(0, $loop);

        $promise = $connector->connect($server->getAddress());
        $promise->cancel();

        $this->setExpectedException('RuntimeException', 'Connection to ' . $server->getAddress() . ' cancelled during TCP/IP handshake');
        Block\await($promise, $loop);
    }

    public function testCancelDuringConnectionShouldNotCreateAnyGarbageReferences()
    {
        if (class_exists('React\Promise\When')) {
            $this->markTestSkipped('Not supported on legacy Promise v1 API');
        }

        gc_collect_cycles();
        gc_collect_cycles(); // clear twice to avoid leftovers in PHP 7.4 with ext-xdebug and code coverage turned on

        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $connector = new TcpConnector($loop);
        $promise = $connector->connect('127.0.0.1:9999');

        $promise->cancel();
        unset($promise);

        $this->assertEquals(0, gc_collect_cycles());
    }
}
