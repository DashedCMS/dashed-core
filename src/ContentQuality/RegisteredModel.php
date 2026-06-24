<?php

// packages/dashed/dashed-core/src/ContentQuality/RegisteredModel.php

namespace Dashed\DashedCore\ContentQuality;

class RegisteredModel
{
    public function __construct(
        public string $modelClass,
        public string $resourceClass,
        public string $label,
    ) {
    }
}
