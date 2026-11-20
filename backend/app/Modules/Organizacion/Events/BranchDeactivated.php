<?php

namespace App\Modules\Organizacion\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class BranchDeactivated
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     */
    public function __construct(
        public readonly int $branchId,
        public readonly array $before,
        public readonly array $after,
        public readonly string $reason,
        public readonly ?int $actorId,
    ) {}
}
