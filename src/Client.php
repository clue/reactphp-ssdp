<?php

namespace Clue\React\Ssdp;

use React\EventLoop\LoopInterface;
use Clue\React\Multicast\Factory as MulticastFactory;
use React\Promise\Deferred;
use RuntimeException;

class Client
{
    const ADDRESS = '239.255.255.250:1900';

    private $loop;
    private $multicast;

    public function __construct(LoopInterface $loop, MulticastFactory $multicast = null)
    {
        if ($multicast === null) {
            $multicast = new MulticastFactory($loop);
        }
        $this->loop = $loop;
        $this->multicast = $multicast;
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

        $timer = $this->loop->addTimer($mx, function() use ($socket, &$deferred) {
            $deferred->resolve();
            $socket->close();
        });

        $loop = $this->loop;
        $deferred = new Deferred(function () use ($socket, &$timer, $loop) {
            // canceling resulting promise cancels timer and closes socket
            $loop->cancelTimer($timer);
            $socket->close();
            throw new RuntimeException('Cancelled');
        });

        $that = $this;
        $socket->on('message', function ($data, $remote) use ($deferred, $that) {
            $message = $that->parseMessage($data, $remote);

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
