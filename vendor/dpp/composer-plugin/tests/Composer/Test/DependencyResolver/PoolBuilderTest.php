<?php

declare(strict_types=1);

namespace Dpp\Test\Composer\Test\DependencyResolver;

use Composer\Composer;
use Composer\Config;
use Composer\DependencyResolver\DefaultPolicy;
use Composer\DependencyResolver\Pool;
use Composer\DependencyResolver\PoolOptimizer;
use Composer\DependencyResolver\Request;
use Composer\EventDispatcher\EventDispatcher;
use Composer\IO\NullIO;
use Composer\Json\JsonFile;
use Composer\Package\AliasPackage;
use Composer\Package\BasePackage;
use Composer\Package\Loader\ArrayLoader;
use Composer\Package\Version\VersionParser;
use Composer\Pcre\Preg;
use Composer\Repository\ArrayRepository;
use Composer\Repository\FilterRepository;
use Composer\Repository\LockArrayRepository;
use Composer\Repository\RepositoryFactory;
use Composer\Repository\RepositorySet;
use Composer\Test\DependencyResolver\PoolBuilderTest as BasePoolBuilderTest;
use Composer\Util\Platform;
use CzProject\GitPhp\Git;
use Dpp\Composer\DppRepositoryClassPlugin;
use Dpp\Composer\Repository\DppBaseProjectRepository;
use PHPUnit\Framework\ExpectationFailedException;

class PoolBuilderTest extends BasePoolBuilderTest
{
    /**
     * @covers \Dpp\Composer\DppRepositoryClassPlugin::poolCreate
     *
     * @uses \Dpp\Composer\DppRepositoryClassPlugin
     * @uses \Dpp\Composer\LockFile
     * @uses \Dpp\Composer\Repository\DppBaseProjectRepository
     * @uses \Dpp\Composer\Repository\Vcs\DppDriver
     *
     * @dataProvider getIntegrationTests
     *
     * @param string[] $expect
     * @param string[] $expectOptimized
     * @param mixed[]  $root
     * @param mixed[]  $requestData
     * @param mixed[]  $packageRepos
     * @param mixed[]  $fixed
     */
    public function testPoolBuilder(string $file, string $message, array $expect, array $expectOptimized, array $root, array $requestData, array $packageRepos, array $fixed, string $dppVersion = ''): void
    {
        $rootAliases = !empty($root['aliases']) ? $root['aliases'] : [];
        $minimumStability = !empty($root['minimum-stability']) ? $root['minimum-stability'] : 'stable';
        $stabilityFlags = !empty($root['stability-flags']) ? $root['stability-flags'] : [];
        $rootReferences = !empty($root['references']) ? $root['references'] : [];
        $stabilityFlags = array_map(static fn ($stability): int => BasePackage::$stabilities[$stability], $stabilityFlags);

        $parser = new VersionParser();
        foreach ($rootAliases as $index => $alias) {
            $rootAliases[$index]['version'] = $parser->normalize($alias['version']);
            $rootAliases[$index]['alias_normalized'] = $parser->normalize($alias['alias']);
        }

        $loader = new ArrayLoader(null, true);
        $packageIds = [];
        $loadPackage = static function ($data) use ($loader, &$packageIds): \Composer\Package\PackageInterface {
            /** @var ?int $id */
            $id = null;
            if (!empty($data['id'])) {
                $id = $data['id'];
                unset($data['id']);
            }

            $pkg = $loader->load($data);

            if (!empty($id)) {
                if (!empty($packageIds[$id])) {
                    throw new \LogicException('Duplicate package id '.$id.' defined');
                }
                $packageIds[$id] = $pkg;
            }

            return $pkg;
        };

        $oldCwd = Platform::getCwd();
        chdir(__DIR__.'/Fixtures/poolbuilder/');

        $repositorySet = new RepositorySet($minimumStability, $stabilityFlags, $rootAliases, $rootReferences);
        $config = new Config(false);
        $rm = RepositoryFactory::manager($io = new NullIO(), $config);
        foreach ($packageRepos as $packages) {
            if (isset($packages['type'])) {
                $repo = RepositoryFactory::createRepo($io, $config, $packages, $rm);
                $repositorySet->addRepository($repo);
                continue;
            }

            $repo = new ArrayRepository();
            if (isset($packages['canonical']) || isset($packages['only']) || isset($packages['exclude'])) {
                $options = $packages;
                $packages = $options['packages'];
                unset($options['packages']);
                $repositorySet->addRepository(new FilterRepository($repo, $options));
            } else {
                $repositorySet->addRepository($repo);
            }
            foreach ($packages as $package) {
                $repo->addPackage($loadPackage($package));
            }
        }
        $repositorySet->addRepository($lockedRepo = new LockArrayRepository());

        if (isset($requestData['locked'])) {
            foreach ($requestData['locked'] as $package) {
                $lockedRepo->addPackage($loadPackage($package));
            }
        }
        $request = new Request($lockedRepo);
        foreach ($requestData['require'] as $package => $constraint) {
            $request->requireName($package, $parser->parseConstraints($constraint));
        }
        if (isset($requestData['allowList'])) {
            $transitiveDeps = Request::UPDATE_ONLY_LISTED;
            if (isset($requestData['allowTransitiveDepsNoRootRequire']) && $requestData['allowTransitiveDepsNoRootRequire']) {
                $transitiveDeps = Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS_NO_ROOT_REQUIRE;
            }
            if (isset($requestData['allowTransitiveDeps']) && $requestData['allowTransitiveDeps']) {
                $transitiveDeps = Request::UPDATE_LISTED_WITH_TRANSITIVE_DEPS;
            }
            $request->setUpdateAllowList($requestData['allowList'], $transitiveDeps);
        }

        foreach ($fixed as $fixedPackage) {
            $request->fixPackage($loadPackage($fixedPackage));
        }

        $composer = $this->createComposerInstance($file, $dppVersion);
        $eventDispatcher = new EventDispatcher($composer, new NullIO());

        $plugin = $this->getMockBuilder(DppRepositoryClassPlugin::class)
            ->onlyMethods(['isApplicable'])
            ->getMock();

        // $plugin->composer = $composer;

        $plugin->expects($this->any())->method('isApplicable')->willReturn(true);

        $plugin->activate($composer, new NullIO());

        $eventDispatcher->addSubscriber($plugin);

        $pool = $repositorySet->createPool($request, new NullIO(), $eventDispatcher);

        $result = $this->getPackageResultSet($pool, $packageIds);

        sort($expect);
        sort($result);

        try {
            $this->assertSame($expect, $result, 'Unoptimized pool does not match expected package set');

            $optimizer = new PoolOptimizer(new DefaultPolicy());
            $result = $this->getPackageResultSet($optimizer->optimize($request, $pool), $packageIds);
            sort($expectOptimized);
            sort($result);

            $this->assertSame($expectOptimized, $result, 'Optimized pool does not match expected package set');
        } catch (ExpectationFailedException $e) {
            file_put_contents(__DIR__.'/Fixtures/poolbuilder/'.$file.'.actual', json_encode($result, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES));
            throw $e;
        }

        chdir($oldCwd);
    }

    private function createComposerInstance(string $file, string $dppVersion): Composer
    {
        $fixturesDir = (string) realpath(__DIR__.'/Fixtures/poolbuilder/');

        $git = new Git();
        $tmpDir = sys_get_temp_dir().'/'.uniqid(
            'dpp-composer-plugin-test',
            true
        );

        $lockFile = str_replace('.test', '.lock', $file);

        $isBranch = false;
        try {
            $versionParser = new VersionParser();
            $versionParser->normalize($dppVersion);
            $baseProjectRepository = $git->init($tmpDir, ['-b' => '1.0.x']);
        } catch (\UnexpectedValueException $e) {
            // Assume it's a branch.
            $baseProjectRepository = $git->init($tmpDir, ['-b' => $dppVersion]);
            $isBranch = true;
        }
        copy($fixturesDir.'/'.$lockFile, $baseProjectRepository->getRepositoryPath().'/composer.lock');

        $baseProjectRepository->addAllChanges();
        $baseProjectRepository->commit('Init repo');
        if (!$isBranch) {
            $baseProjectRepository->createTag($dppVersion);
        }

        putenv('COMPOSER_DPP_BASE_PROJECT_GIT_URL='.$baseProjectRepository->getRepositoryPath());

        $composer = new Composer();
        $config = new Config();
        $config->merge(['config' => ['home' => __DIR__.'/Fixtures/poolbuilder']]);
        $composer->setConfig($config);
        $package = $this->getMockBuilder('Composer\Package\RootPackageInterface')->getMock();
        $composer->setPackage($package);
        $composer->setInstallationManager($this->getMockBuilder('Composer\Installer\InstallationManager')->disableOriginalConstructor()->getMock());

        $dppRepository = new DppBaseProjectRepository(['baseProjectGitUrl' => $baseProjectRepository->getRepositoryPath()], new NullIO(), $config, $this->getMockBuilder('Composer\Util\HttpDownloader')->disableOriginalConstructor()->getMock(), new EventDispatcher($composer, new NullIO()));

        $repositoryManager = $this->getMockBuilder('Composer\Repository\RepositoryManager')->disableOriginalConstructor()->getMock();
        $repositoryManager->expects($this->any())->method('createRepository')->willReturn($dppRepository);
        $composer->setRepositoryManager($repositoryManager);

        return $composer;
    }

    /**
     * @param array<int, BasePackage> $packageIds
     *
     * @return list<string|int>
     */
    private function getPackageResultSet(Pool $pool, array $packageIds): array
    {
        $result = [];
        for ($i = 1, $count = \count($pool); $i <= $count; ++$i) {
            $result[] = $pool->packageById($i);
        }

        return array_map(static function (BasePackage $package) use ($packageIds) {
            if ($id = array_search($package, $packageIds, true)) {
                return $id;
            }

            $suffix = '';
            if ($package->getSourceReference()) {
                $suffix = '#'.$package->getSourceReference();
            }
            if ($package->getRepository() instanceof LockArrayRepository) {
                $suffix .= ' (locked)';
            }

            if ($package instanceof AliasPackage) {
                if ($id = array_search($package->getAliasOf(), $packageIds, true)) {
                    return (string) $package->getName().'-'.$package->getVersion().$suffix.' (alias of '.$id.')';
                }

                return (string) $package->getName().'-'.$package->getVersion().$suffix.' (alias of '.$package->getAliasOf()->getVersion().')';
            }

            return (string) $package->getName().'-'.$package->getVersion().$suffix;
        }, $result);
    }

    /**
     * @return array<string, array<string>>
     */
    public static function getIntegrationTests(): array
    {
        $fixturesDir = (string) realpath(__DIR__.'/Fixtures/poolbuilder/');
        $tests = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($fixturesDir), \RecursiveIteratorIterator::LEAVES_ONLY) as $file) {
            $file = (string) $file;

            if (!Preg::isMatch('/\.test$/', $file)) {
                continue;
            }

            try {
                $testData = self::readTestFile($file, $fixturesDir);

                $message = $testData['TEST'];
                $dppVersion = $testData['DPP-VERSION'];

                $request = JsonFile::parseJson($testData['REQUEST']);
                $root = !empty($testData['ROOT']) ? JsonFile::parseJson($testData['ROOT']) : [];

                $packageRepos = JsonFile::parseJson($testData['PACKAGE-REPOS']);
                $fixed = [];
                if (!empty($testData['FIXED'])) {
                    $fixed = JsonFile::parseJson($testData['FIXED']);
                }
                $expect = JsonFile::parseJson($testData['EXPECT']);
                $expectOptimized = !empty($testData['EXPECT-OPTIMIZED']) ? JsonFile::parseJson($testData['EXPECT-OPTIMIZED']) : $expect;
            } catch (\Exception $e) {
                exit(sprintf('Test "%s" is not valid: '.$e->getMessage(), str_replace($fixturesDir.'/', '', $file)));
            }

            $tests[basename($file)] = [str_replace($fixturesDir.'/', '', $file), $message, $expect, $expectOptimized, $root, $request, $packageRepos, $fixed, $dppVersion];
        }

        return $tests;
    }

    /**
     * @return array<string, string>
     */
    protected static function readTestFile(string $file, string $fixturesDir): array
    {
        $tokens = Preg::split('#(?:^|\n*)--([A-Z-]+)--\n#', file_get_contents($file), -1, \PREG_SPLIT_DELIM_CAPTURE);

        $sectionInfo = [
            'TEST' => true,
            'DPP-VERSION' => true,
            'ROOT' => false,
            'REQUEST' => true,
            'FIXED' => false,
            'PACKAGE-REPOS' => true,
            'EXPECT' => true,
            'EXPECT-OPTIMIZED' => false,
        ];

        $section = null;
        $data = [];
        foreach ($tokens as $i => $token) {
            if (null === $section && empty($token)) {
                continue; // skip leading blank
            }

            if (null === $section) {
                if (!isset($sectionInfo[$token])) {
                    throw new \RuntimeException(sprintf('The test file "%s" must not contain a section named "%s".', str_replace($fixturesDir.'/', '', $file), $token));
                }
                $section = $token;
                continue;
            }

            $sectionData = $token;

            $data[$section] = $sectionData;
            $section = $sectionData = null;
        }

        foreach ($sectionInfo as $section => $required) {
            if ($required && !isset($data[$section])) {
                throw new \RuntimeException(sprintf('The test file "%s" must have a section named "%s".', str_replace($fixturesDir.'/', '', $file), $section));
            }
        }

        return $data;
    }
}
