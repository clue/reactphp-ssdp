<?php

namespace Clue\Tests\React\Ssdp;

use Clue\React\Ssdp\Client;
use React\EventLoop\Loop;

class ClientTest extends TestCase
{
    public function testConstructWithoutLoopAssignsLoopAutomatically()
    {
        $client = new Client();

        $ref = new \ReflectionProperty($client, 'loop');
        $ref->setAccessible(true);
        $loop = $ref->getValue($client);

        $this->assertInstanceOf('React\EventLoop\LoopInterface', $loop);
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCtor()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        new Client($loop);
    }

    public function testSearchCancel()
    {
        $loop = $this->getMockBuilder('React\EventLoop\LoopInterface')->getMock();
        $multicast = $this->getMockBuilder('Clue\React\Multicast\Factory')->disableOriginalConstructor()->getMock();
        $client = new Client($loop, $multicast);

        $socket = $this->getMockBuilder('React\Datagram\SocketInterface')->getMock();
        $socket->expects($this->once())->method('send');

        // prefer newer EventLoop 1.0/0.5+ TimerInterface or fall back to legacy namespace
        $timer = $this->getMockBuilder(
            interface_exists('React\EventLoop\TimerInterface') ? 'React\EventLoop\TimerInterface' : 'React\EventLoop\Timer\TimerInterface'
        )->getMock();

        $loop->expects($this->once())->method('addTimer')->willReturn($timer);
        $loop->expects($this->once())->method('cancelTimer')->with($timer);

        $multicast->expects($this->once())->method('createSender')->will($this->returnValue($socket));

        $promise = $client->search();

        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        if (!($promise instanceof \React\Promise\CancellablePromiseInterface)) {
            $this->markTestSkipped();
        }

        $socket->expects($this->once())->method('close');

        $promise->cancel();

        $promise->then(null, $this->expectCallableOnce());
    }

    public function testSearchTimeout()
    {
        $client = new Client();

        $promise = $client->search('ssdp:all', 0.01);

        Loop::run();

        $promise->then($this->expectCallableOnce(), $this->expectCallableNever(), $this->expectCallableNever());
    }

    public function testCtorThrowsForInvalidLoop()
    {
        if (method_exists($this, 'expectException')) {
            // PHPUnit 5.2+
            $this->expectException('InvalidArgumentException');
            $this->expectExceptionMessage('Argument #1 ($loop) expected null|React\EventLoop\LoopInterface');
        } else {
            // legacy PHPUnit
            $this->setExpectedException('InvalidArgumentException', 'Argument #1 ($loop) expected null|React\EventLoop\LoopInterface');
        }

        new Client('loop');
    }

    public function testCtorThrowsForInvalidMulticast()
    {
        if (method_exists($this, 'expectException')) {
            // PHPUnit 5.2+
            $this->expectException('InvalidArgumentException');
            $this->expectExceptionMessage('Argument #2 ($multicast) expected null|Clue\React\Multicast\Factory');
        } else {
            // legacy PHPUnit
            $this->setExpectedException('InvalidArgumentException', 'Argument #2 ($multicast) expected null|Clue\React\Multicast\Factory');
        }

        new Client(null, 'multicast');
    }
}
