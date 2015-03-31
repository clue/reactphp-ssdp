<?php

use Clue\React\Ssdp\Client;
use React\EventLoop\Factory;

class ClientTest extends TestCase
{
    public function testCtor()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        new Client($loop);
    }

    public function testSearchCancel()
    {
        $loop = $this->getMock('React\EventLoop\LoopInterface');
        $multicast = $this->getMockBuilder('Clue\React\Multicast\Factory')->disableOriginalConstructor()->getMock();
        $client = new Client($loop, $multicast);

        $socket = $this->getMock('React\Datagram\SocketInterface');
        $socket->expects($this->once())->method('send');

        $timer = $this->getMock('React\EventLoop\Timer\TimerInterface');
        $loop->expects($this->once())->method('addTimer')->will($this->returnValue($timer));

        $multicast->expects($this->once())->method('createSender')->will($this->returnValue($socket));

        $promise = $client->search();

        $this->assertInstanceOf('React\Promise\PromiseInterface', $promise);

        if (!($promise instanceof React\Promise\CancellablePromiseInterface)) {
            $this->markTestSkipped();
        }

        $socket->expects($this->once())->method('close');
        $timer->expects($this->once())->method('cancel');

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
