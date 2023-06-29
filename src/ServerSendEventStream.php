<?php

declare(strict_types=1);

namespace AUS\ServerSendEvents;

use Psr\Http\Message\RequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\ImmediateResponseException;

final class ServerSendEventStream
{
    private int $lastMessageTime = 0;

    public function __construct(
        private int $eventId = 0,
        private readonly int $paddingLength = 4096,
        private readonly int $paddingChunckLength = 1000,
        private readonly int $pingTimeout = 20,
    ) {
        header("Cache-Control: no-store");
        header("Content-Type: text/event-stream;charset=UTF-8");
    }

    /**
     * @param mixed[]|null $message
     */
    public function sendMessage(?array $message, string $eventName = 'message'): void
    {
        $this->lastMessageTime = time();
        $eventData = json_encode($message, JSON_THROW_ON_ERROR);

        $this->pad(strlen($eventData));

        echo sprintf("id: %s\n", $this->eventId++);
        echo sprintf("event: %s\n", $eventName);
        echo 'data: ' . $eventData . "\n\n";

        $this->pad(strlen($eventData));

        ob_end_flush();
        flush();

        if (connection_aborted()) {
            if (class_exists(ImmediateResponseException::class) && class_exists(HtmlResponse::class)) {
                throw new ImmediateResponseException(new HtmlResponse(''));
            }

            die();
        }
    }

    private function pad(int $payloadSize): void
    {
        for ($i = $payloadSize; $i < $this->paddingLength; $i += $this->paddingChunckLength) {
            echo sprintf("id: %s\n", $this->eventId++);
            echo "event: padding\n";
            echo 'data: ' . str_repeat('A', $this->paddingLength) . "\n\n";
        }
    }

    public function ping(): void
    {
        if ($this->lastMessageTime < (time() - $this->pingTimeout)) {
            $this->sendMessage(null, 'ping');
        }
    }

    public static function isEventStream(RequestInterface $request = null): bool
    {
        $accept = $request?->getHeaderLine('Accept') ?? $_SERVER['HTTP_ACCEPT'] ?? '';
        return $accept === 'text/event-stream';
    }
}
