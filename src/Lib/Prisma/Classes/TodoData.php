<?php

declare(strict_types=1);

namespace Lib\Prisma\Classes;

use DateTime;

class TodoData
{

    public ?TodoData $_avg = null;
    public ?TodoData $_count = null;
    public ?TodoData $_max = null;
    public ?TodoData $_min = null;
    public ?TodoData $_sum = null;
    public ?int $id;
    public string $title;
    public bool $completed;
    public DateTime|string $createdAt;
    public DateTime|string $updatedAt;

    public function __construct(
        string $title,
        bool $completed = false,
        DateTime|string $createdAt = new DateTime(),
        DateTime|string $updatedAt = new DateTime(),
        ?int $id = null,
    ) {
        $this->id = $id;
        $this->title = $title;
        $this->completed = $completed;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'completed' => $this->completed,
            'createdAt' => $this->createdAt ? $this->createdAt->format('Y-m-d H:i:s') : null,
            'updatedAt' => $this->updatedAt ? $this->updatedAt->format('Y-m-d H:i:s') : null
        ];
    }
}