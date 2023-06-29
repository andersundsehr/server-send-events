<?php

declare(strict_types=1);

namespace AUS\ServerSendEvents;

use RuntimeException;

final class FileEventTrigger
{
    /** @var array<string, float>  */
    private array $initalTimes = [];

    public function __construct(private readonly ?ServerSendEventStream $stream = null, private readonly string $directory = __DIR__ . '/../__data', private readonly int $usleepTimer = 100_000)
    {
        if (mkdir($concurrentDirectory = $this->directory, 0777, true)) {
            return;
        }

        if (is_dir($concurrentDirectory)) {
            return;
        }

        throw new RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
    }

    public function trigger(string $eventName): void
    {
        $filename = $this->directory . '/' . $eventName;
        file_put_contents($filename, microtime(true));
    }

    public function sleepUntilTrigger(string $eventName, int $watchUntil = 0): void
    {
        $filename = $this->directory . '/' . $eventName;
        clearstatcache(true);
        $this->initalTimes[$eventName] ??= (float)(file_exists($filename) ? file_get_contents($filename) : 0);
        do {
            clearstatcache(true);
            $mtime = (float)(file_exists($filename) ? file_get_contents($filename) : 0);
            if ($mtime > $this->initalTimes[$eventName]) {
                $this->initalTimes[$eventName] = $mtime;
                return;
            }

            usleep($this->usleepTimer);
            $this->stream?->ping();
        } while (($watchUntil === 0) || ($watchUntil > time()));
    }
}
