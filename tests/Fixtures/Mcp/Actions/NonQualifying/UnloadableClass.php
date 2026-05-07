<?php

declare(strict_types=1);

// This class has a namespace that is NOT in the PSR-4 autoload map.
// The scanner will extract the FQCN but class_exists() will return false,
// exercising the early-return guard in AttributeScanner::qualifies().

namespace Some\Totally\Unloadable\Namespace;

class UnloadableClass {}
