<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention\Contracts;

use Dashed\DashedCore\Retention\Termijn;

/**
 * Voor alles wat geen kale datumverwijdering is. `AuditPruner` kleedt een scan
 * uit tot zijn samenvatting, de 404-opruiming telt achteraf de teller op de
 * ouderrij opnieuw, en `queue:prune-failed` is een command van Laravel zelf.
 */
interface Opruimer
{
    /**
     * @return int aantal opgeruimde rijen; bij $droog het aantal dat opgeruimd zou worden
     */
    public function ruimOp(Termijn $termijn, int $portie, bool $droog): int;
}
