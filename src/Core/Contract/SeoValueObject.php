<?php

declare(strict_types=1);

namespace Bnomei\Seo\Core\Contract;

use JsonSerializable;

interface SeoValueObject extends JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}
