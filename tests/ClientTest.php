<?php

namespace Clue\Tests\React\Ssdp;

use Clue\React\Ssdp\Client;
use React\EventLoop\Factory;

class ClientTest extends TestCase
{
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
        $loop = Factory::create();
        $client = new Client($loop);

        $promise = $client->search('ssdp:all', 0.01);

        $loop->run();

        $promise->then($this->expectCallableOnce(), $this->expectCallableNever(), $this->expectCallableNever());
    }
}
