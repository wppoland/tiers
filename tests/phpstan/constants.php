<?php

declare(strict_types=1);

/**
 * PHPStan bootstrap: define plugin constants so tiers.php and src/ can be analysed
 * without a running WordPress environment.
 */

define( 'ABSPATH', '/tmp/' );
define( 'Plogins\Tiers\VERSION', '0.1.0' );
define( 'Plogins\Tiers\PLUGIN_FILE', '/tmp/tiers.php' );
define( 'Plogins\Tiers\PLUGIN_DIR', '/tmp' );
define( 'Plogins\Tiers\MIN_PHP_VERSION', '8.1.0' );
define( 'Plogins\Tiers\MIN_WC_VERSION', '8.0.0' );
