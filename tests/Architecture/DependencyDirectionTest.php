<?php

declare(strict_types=1);

namespace Vending\Tests\Architecture;

use function dirname;
use function explode;
use function file_get_contents;

use FilesystemIterator;

use function implode;
use function in_array;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function preg_match_all;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function sort;

use SplFileInfo;

use function sprintf;
use function strlen;
use function substr;

/**
 * Keeps the dependency direction honest with the analyser ADR 0001 promised:
 * dependencies point inward, never out. Domain imports only Domain;
 * Application may add Domain; Infrastructure may see everything.
 *
 * Hand-rolled on purpose — no tool dependency for a one-rule check. The scan
 * reads `use Vending\…` statements, which here are the whole project-level
 * dependency surface: the style fixer enforces imports over inline qualified
 * names, so a reference that evades this test cannot pass `make cs`.
 */
final class DependencyDirectionTest extends TestCase
{
    private const array ALLOWED_IMPORTS = [
        'Domain' => ['Domain'],
        'Application' => ['Domain', 'Application'],
        'Infrastructure' => ['Domain', 'Application', 'Infrastructure'],
    ];

    #[Test]
    public function dependencies_point_inward(): void
    {
        $files = $this->sourceFiles();
        self::assertNotSame([], $files, 'The scan found no source files; the rule is not being checked.');

        $violations = [];
        foreach ($files as $path) {
            $layer = $this->layerOf($path);
            foreach ($this->projectImports($path) as $import) {
                if (!in_array($this->layerOf($import), self::ALLOWED_IMPORTS[$layer], true)) {
                    $violations[] = sprintf('%s (%s) imports %s', $path, $layer, $import);
                }
            }
        }

        self::assertSame([], $violations, "Outward dependencies found:\n" . implode("\n", $violations));
    }

    /**
     * @return list<string> absolute paths of every PHP file under src/
     */
    private function sourceFiles(): array
    {
        $files = [];
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(self::sourceRoot(), FilesystemIterator::SKIP_DOTS),
        );
        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }
        sort($files);

        return $files;
    }

    /**
     * The layer is the first segment after src/ for a path, or after the
     * vendor namespace for an import — Domain, Application or Infrastructure.
     */
    private function layerOf(string $pathOrImport): string
    {
        $relative = str_starts_with($pathOrImport, 'Vending\\')
            ? substr($pathOrImport, strlen('Vending\\'))
            : substr($pathOrImport, strlen(self::sourceRoot()) + 1);

        return explode(str_contains($relative, '\\') ? '\\' : '/', $relative)[0];
    }

    /**
     * @return list<string> the `use Vending\…` imports of the file
     */
    private function projectImports(string $path): array
    {
        $code = file_get_contents($path);
        self::assertNotFalse($code);

        preg_match_all('/^use (Vending\\\\[A-Za-z0-9_\\\\]+)/m', $code, $matches);

        return $matches[1];
    }

    private static function sourceRoot(): string
    {
        return dirname(__DIR__, 2) . '/src';
    }
}
