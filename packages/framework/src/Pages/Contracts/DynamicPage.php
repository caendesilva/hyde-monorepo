<?php

declare(strict_types=1);

namespace Hyde\Pages\Contracts;

/**
 * @experimental This feature is experimental and may change substantially before the 1.0.0 release.
 *
 * This interface is used to mark page classes that are dynamically generated,
 * (i.e. not based on a source file), or that have dynamic path information.
 *
 * These page classes are excluded by the Hyde Auto Discovery process,
 * they must therefore be added to the HydeKernel by the developer,
 * as Hyde won't have the needed info to do so automatically.
 *
 * @todo See if we can make source/destination directories and file extension optional and use that to determine if a page is dynamic or not.
 */
interface DynamicPage
{
    //
}
