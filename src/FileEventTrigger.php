<?php

declare(strict_types=1);

namespace AUS\ServerSendEvents;

use RuntimeException;

final class FileEventTrigger
{
    /** @var array<string, int>  */
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
        touch($this->directory . '/' . $eventName);
    }

    public function sleepUntilTrigger(string $eventName, int $watchUntil = 0): void
    {
        $filename = $this->directory . '/' . $eventName;
        clearstatcache(true);
        $this->initalTimes[$eventName] ??= (int)(file_exists($filename) ? filemtime($filename) : 0);
        do {
            clearstatcache(true);
            $mtime = (int)(file_exists($filename) ? filemtime($filename) : 0);
            if ($mtime > $this->initalTimes[$eventName]) {
                $this->initalTimes[$eventName] = $mtime;
                return;
            }

            usleep($this->usleepTimer);
            $this->stream?->ping();
        } while (($watchUntil === 0) || ($watchUntil > time()));
    }
}
