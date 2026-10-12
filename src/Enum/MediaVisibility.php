<?php

declare(strict_types=1);

namespace Neox\MediaBundle\Enum;

/**
 * Visibility level of a Media or MediaCollection.
 *
 * Only evaluated when neox_media.ownership.enabled is true.
 * When disabled, entities keep the default PRIVATE value but it is ignored.
 */
enum MediaVisibility: string
{
    /** Only the owner (and admin bypass) can access. */
    case PRIVATE = 'private';

    /** Any authenticated user of the application can view. */
    case SHARED = 'shared';

    /** Publicly linkable (e.g. usable on public pages). */
    case PUBLIC = 'public';
}
