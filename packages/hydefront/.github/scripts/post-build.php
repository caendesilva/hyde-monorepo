<?php

declare(strict_types=1);

require_once __DIR__ . '/minima.php';

exit(main(function (): int {
    $baseDir = __DIR__.'/../../';

    // If running in monorepo
    if (file_exists($baseDir.'../../composer.json') && str_contains(file_get_contents($baseDir.'../../composer.json'), 'hyde/monorepo')) {
        // Check that HydeFront entry in root package lock is up-to-date with the package.json
        $this->info('Verifying root package lock...');

        $rootPackageLock = json_decode(file_get_contents($baseDir.'../../package-lock.json'), true);
        $hydeFrontPackageLock = $rootPackageLock['dependencies']['hydefront'];
        $hydeFrontPackage = json_decode(file_get_contents($baseDir.'../../packages/hydefront/package.json'), true);
        $hydeFrontVersion = $hydeFrontPackage['version'];

        if (! $this->hasOption('skip-root-version-check')) {
            if ($hydeFrontPackageLock['version'] !== $hydeFrontVersion) {
                $this->error('Version mismatch in root package-lock.json and packages/hydefront/package.json:');
                $this->warning("Expected hydefront to have version '$hydeFrontPackageLock[version]', but found '$hydeFrontVersion'");
                $this->warning("Please run 'npm update hydefront'");
                return 1;
            } else {
                $this->info('Root package lock verified. All looks good!');
            }
        }
    }

    return 0;
}));
