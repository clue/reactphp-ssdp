<?php

namespace Clue\React\Ssdp;

use Clue\React\Multicast\Factory as MulticastFactory;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use RuntimeException;

class Client
{
    const ADDRESS = '239.255.255.250:1900';

    /** @var LoopInterface */
    private $loop;

    /** @var MulticastFactory */
    private $multicast;

    /**
     * This class takes an optional `LoopInterface|null $loop` parameter that can be used to
     * pass the event loop instance to use for this object. You can use a `null` value
     * here in order to use the [default loop](https://github.com/reactphp/event-loop#loop).
     * This value SHOULD NOT be given unless you're sure you want to explicitly use a
     * given event loop instance.
     *
     * @param ?LoopInterface $loop
     * @param ?MulticastFactory $multicast
     */
    public function __construct(LoopInterface $loop = null, MulticastFactory $multicast = null)
    {
        $this->loop = $loop ?: Loop::get();
        $this->multicast = $multicast ?: new MulticastFactory($this->loop);
    }

    public function search($searchTarget = 'ssdp:all', $mx = 2)
    {
        $data  = "M-SEARCH * HTTP/1.1\r\n";
        $data .= "HOST: " . self::ADDRESS . "\r\n";
        $data .= "MAN: \"ssdp:discover\"\r\n";
        $data .= "MX: $mx\r\n";
        $data .= "ST: $searchTarget\r\n";
        $data .= "\r\n";

        $socket = $this->multicast->createSender();
        // TODO: The TTL for the IP packet SHOULD default to 2 and SHOULD be configurable.

        $messages = array();

        $loop = $this->loop;
        $deferred = new Deferred(function () use ($socket, &$timer, $loop) {
            // canceling resulting promise cancels timer and closes socket
            $loop->cancelTimer($timer);
            $socket->close();
            throw new RuntimeException('Cancelled');
        });

        $timer = $this->loop->addTimer($mx, function() use ($socket, &$deferred, &$messages) {
        	$deferred->resolve($messages);
        	$socket->close();
        });

        $that = $this;
        $socket->on('message', function ($data, $remote) use (&$messages, $that) {
            $message = $that->parseMessage($data, $remote);
            $messages[] = $message;

            $deferred->progress($message);
        });

        $socket->send($data, self::ADDRESS);

        return $deferred->promise();
    }

    /** @internal */
    public function parseMessage($message, $remote)
    {
        // TODO: parse message into message model
        $message = array(
            'data' => $message,
            '_sender' => $remote
        );
        return $message;
    }
}
