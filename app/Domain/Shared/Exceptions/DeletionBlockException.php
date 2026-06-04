<?php

namespace App\Domain\Shared\Exceptions;

use Exception;

class DeletionBlockException extends Exception
{
    /**
     * @param  array<string, int>  $blockingReasons
     */
    public function __construct(
        private readonly array $blockingReasons,
    ) {
        parent::__construct($this->formatMessage());
    }

    /**
     * @return array<string, int>
     */
    public function blockingReasons(): array
    {
        return $this->blockingReasons;
    }

    private function formatMessage(): string
    {
        $parts = [];

        foreach ($this->blockingReasons as $label => $count) {
            $parts[] = "{$count} {$label}";
        }

        return 'Cannot delete: used on '.implode(', ', $parts).'.';
    }
}
