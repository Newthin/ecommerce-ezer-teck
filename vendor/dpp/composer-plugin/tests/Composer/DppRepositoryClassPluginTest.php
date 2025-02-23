<?php

declare(strict_types=1);

namespace Dpp\Test\Composer;

use Composer\Composer;
use Composer\Package\Link;
use Composer\Package\Package;
use Composer\Repository\RepositoryManager;
use Composer\Semver\Constraint\Constraint;
use Composer\Util\HttpDownloader;
use Dpp\Composer\DppRepositoryClassPlugin;
use Dpp\Composer\Repository\DppBaseProjectRepository;
use PHPUnit\Framework\TestCase;

class DppRepositoryClassPluginTest extends TestCase
{
    use SetupRepositoryTrait;

    public const TEST_WITHOUT_ENDPOINT = 0;
    public const TEST_WITH_CONFIG = 1;
    public const TEST_WITH_ENV_VAR = 2;

    /**
     * @dataProvider getConfigProvider
     *
     * @covers \Dpp\Composer\DppRepositoryClassPlugin::activate
     * @covers \Dpp\Composer\DppRepositoryClassPlugin::isApplicable
     * @covers \Dpp\Composer\DppRepositoryClassPlugin::getInstalledDppVersion
     *
     * @uses \Dpp\Composer\DppRepositoryClassPlugin
     * @uses \Dpp\Composer\Repository\DppBaseProjectRepository
     * @uses \Dpp\Composer\LockFile
     * @uses \Dpp\Composer\Repository\Vcs\DppDriver
     */
    public function testActivate(int $endpointMode, bool $versionIsRequired = true): void
    {
        $plugin = new DppRepositoryClassPlugin();

        $installationManager = $this->getMockBuilder('Composer\Installer\InstallationManager')
          ->disableOriginalConstructor()
          ->getMock();

        // Method call is not expected if activation throws exception.
        if (static::TEST_WITHOUT_ENDPOINT !== $endpointMode) {
            $installationManager->expects($this->once())
                ->method('addInstaller')
                ->with($this->isInstanceOf('Dpp\Composer\Installer\DppBaseProjectInstaller'));
        }

        $rootPackage = $this->getMockBuilder('Composer\Package\RootPackageInterface')
          ->disableOriginalConstructor()
          ->getMock();

        if (static::TEST_WITH_ENV_VAR === $endpointMode) {
            putenv('COMPOSER_DPP_BASE_PROJECT_GIT_URL='.$this->baseProjectRepository->getRepositoryPath());
        } elseif (static::TEST_WITH_CONFIG === $endpointMode) {
            $rootPackage->expects($this->any())
              ->method('getExtra')
              ->willReturn(['dpp-base-project-git-url' => $this->baseProjectRepository->getRepositoryPath()]);
        }
        $required = $versionIsRequired ? ['dpp/version' => new Link('dpp/version', 'dpp/version', new Constraint('<=', '1.1.0'))] : [];
        $rootPackage->expects($this->any())->method('getRequires')->willReturn($required);

        $installedRepositoriy = $this->getMockBuilder('Composer\Repository\InstalledRepositoryInterface')
          ->disableOriginalConstructor()
          ->getMock();

        $io = $this->getMockBuilder('Composer\IO\IOInterface')
          ->disableOriginalConstructor()
          ->getMock();

        $config = $this->getMockBuilder('Composer\Config')->getMock();
        $config->expects($this->any())->method('get')->willReturn(
            'test',
            true,
            'test',
            true,
            'test',
            true,
        );

        $repositoryManager = new RepositoryManager($io, $config, $this->createMock(HttpDownloader::class));
        $repositoryManager->setLocalRepository($installedRepositoriy);
        $repositoryManager->setRepositoryClass('composer', 'Composer\Repository\ComposerRepository');
        $repositoryManager->setRepositoryClass('vcs', 'Composer\Repository\VcsRepository');

        $locker = $this->getMockBuilder('Composer\Package\Locker')
            ->disableOriginalConstructor()
            ->getMock();
        $locker->expects($this->any())->method('isLocked')
            ->willReturn(true);

        $locker->expects($this->any())->method('getLockData')
            ->willReturn([
                'packages' => [
                    [
                        'name' => 'dpp/version',
                        'version' => '1.1.0',
                    ],
                    [
                        'name' => 'drupal/core',
                        'version' => '10.0.0',
                    ],
                ],
            ]);

        $composer = new Composer();
        $composer->setRepositoryManager($repositoryManager);
        $composer->setInstallationManager($installationManager);
        $composer->setPackage($rootPackage);
        $composer->setConfig($config);
        $composer->setLocker($locker);

        if (static::TEST_WITHOUT_ENDPOINT === $endpointMode) {
            $this->expectException('\RuntimeException');
        }

        $plugin->activate($composer, $io);

        if (static::TEST_WITHOUT_ENDPOINT === $endpointMode) {
            return;
        }

        $repositoryTypes = array_map(static fn ($repository) => $repository::class, $repositoryManager->getRepositories());
        $this->assertContains(DppBaseProjectRepository::class, $repositoryTypes);

        foreach ($repositoryManager->getRepositories() as $repository) {
            if (DppBaseProjectRepository::class === $repository::class) {
                $this->assertCount(5, $repository->getPackages());
                $this->assertSame('dpp repo ('.$this->baseProjectRepository->getRepositoryPath().')', $repository->getRepoName());

                $packageVersions = array_map(static fn ($package) => $package->getPrettyVersion(), $repository->getPackages());
                $this->assertContains('1.0.0', $packageVersions);
                $this->assertContains('1.1.0', $packageVersions);

                $dppPackage = $repository->findPackage('dpp/version', '1.0.0');
                $this->assertArrayHasKey('drupal/core', $dppPackage->getExtra()['patches']);
            }
        }
        if (static::TEST_WITH_ENV_VAR === $endpointMode) {
            putenv('COMPOSER_DPP_BASE_PROJECT_GIT_URL=');
        }
    }

    /**
     * @dataProvider getConstraintTestProvider
     *
     * @covers \Dpp\Composer\DppRepositoryClassPlugin::preCommandRun
     * @covers \Dpp\Composer\DppRepositoryClassPlugin::isManagedPackage
     * @covers \Dpp\Composer\Repository\Vcs\DppDriver::__construct
     * @covers \Dpp\Composer\Repository\Vcs\DppDriver::getFile
     * @covers \Dpp\Composer\Repository\Vcs\DppDriver::getBaseFile
     *
     * @uses \Dpp\Composer\DppRepositoryClassPlugin
     * @uses \Dpp\Composer\LockFile
     * @uses \Dpp\Composer\Repository\DppBaseProjectRepository
     * @uses \Dpp\Composer\Repository\Vcs\DppDriver
     */
    public function testConstraint(array $packages, array $expected): void
    {
        $plugin = new DppRepositoryClassPlugin();

        $installationManager = $this->getMockBuilder('Composer\Installer\InstallationManager')
            ->disableOriginalConstructor()
            ->getMock();

        // Method call is not expected if activation throws exception.
        $installationManager->expects($this->once())
            ->method('addInstaller')
            ->with($this->isInstanceOf('Dpp\Composer\Installer\DppBaseProjectInstaller'));

        $rootPackage = $this->getMockBuilder('Composer\Package\RootPackageInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $rootPackage->expects($this->any())
            ->method('getExtra')
            ->willReturn(['dpp-base-project-git-url' => $this->baseProjectRepository->getRepositoryPath()]);
        $required = ['dpp/version' => new Link('dpp/version', 'dpp/version', new Constraint('<=', '1.1.0'))];
        $rootPackage->expects($this->any())
            ->method('getRequires')
            ->willReturn($required);

        $installedRepository = $this->getMockBuilder('Composer\Repository\InstalledRepositoryInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $installedRepository->expects($this->any())
            ->method('findPackages')
            ->willReturn([
               new Package('dpp/version', '1.1.0', '1.1.0'),
            ]);

        $io = $this->getMockBuilder('Composer\IO\IOInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $config = $this->getMockBuilder('Composer\Config')->getMock();
        $config->expects($this->any())->method('get')->willReturn(
            'test',
            true,
            'test',
            true,
            'test',
            true,
        );

        $repositoryManager = new RepositoryManager($io, $config, $this->createMock(HttpDownloader::class));
        $repositoryManager->setLocalRepository($installedRepository);
        $repositoryManager->setRepositoryClass('composer', 'Composer\Repository\ComposerRepository');
        $repositoryManager->setRepositoryClass('vcs', 'Composer\Repository\VcsRepository');

        $locker = $this->getMockBuilder('Composer\Package\Locker')
            ->disableOriginalConstructor()
            ->getMock();
        $locker->expects($this->any())->method('isLocked')
            ->willReturn(false);

        $composer = new Composer();
        $composer->setRepositoryManager($repositoryManager);
        $composer->setInstallationManager($installationManager);
        $composer->setPackage($rootPackage);
        $composer->setConfig($config);
        $composer->setLocker($locker);

        $input = $this->getMockBuilder('Symfony\Component\Console\Input\Input')
            ->disableOriginalConstructor()
            ->getMock();
        $input->expects($this->any())
            ->method('hasArgument')
            ->withAnyParameters()
            ->willReturn(true);
        $input->expects($this->any())
            ->method('getArgument')
            ->willReturnMap([
                ['command', 'require'],
                ['packages', $packages],
            ]);
        $input->expects($this->any())
            ->method('setArgument')
            ->with('packages', $expected)
            ->willReturn(null);
        $event = $this->getMockBuilder('Composer\Plugin\PreCommandRunEvent')
            ->disableOriginalConstructor()
            ->getMock();

        $event->expects($this->any())
            ->method('getInput')
            ->willReturn($input);
        // Activate plugin first to set composer and io fields.
        $plugin->activate($composer, $io);

        $plugin->preCommandRun($event);
    }

    public static function getConfigProvider(): array
    {
        return [
            'Test exception when no endpoint is set' => [static::TEST_WITHOUT_ENDPOINT, true],
            'Test with endpoint set as extra config' => [static::TEST_WITH_CONFIG, true],
            'Test with endpoint set as env variable' => [static::TEST_WITH_ENV_VAR, true],
            'Test with endpoint set as extra config and dpp/version not required' => [static::TEST_WITH_CONFIG, false],
        ];
    }

    public static function getConstraintTestProvider()
    {
        return [
            'No constraint' => [
                ['drupal/core'],
                ['drupal/core:*'],
            ],
            'Constraint' => [
                ['drupal/core:^10'],
                ['drupal/core:^10'],
            ],
            'Mixed' => [
                ['drupal/core:^10', 'drupal/smtp'],
                ['drupal/core:^10', 'drupal/smtp:*'],
            ],
            'Package outside dpp' => [
                ['random/package'],
                ['random/package'],
            ],
        ];
    }
}
