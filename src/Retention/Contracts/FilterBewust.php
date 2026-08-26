<?php

declare(strict_types=1);

namespace Dashed\DashedCore\Retention\Contracts;

/**
 * Zegt dat een opruimer Termijn::filter() en Termijn::terugvalkolom() echt
 * toepast. Alleen TabelOpruimer en SleutelOpruimer doen dat; de rest heeft een
 * eigen, vaste query.
 *
 * Zonder deze markering zou een behoudregel op een entry met een eigen
 * opruimer stil in de prullenbak vallen, en dan verdwijnen er rijen die
 * iemand expliciet wilde bewaren. PruneRunner weigert die combinatie daarom
 * hardop in plaats van hem te negeren.
 */
interface FilterBewust
{
}
