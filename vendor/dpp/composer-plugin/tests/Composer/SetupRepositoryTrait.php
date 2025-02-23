<?php

declare(strict_types=1);

namespace Dpp\Test\Composer;

use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;

trait SetupRepositoryTrait
{
    private GitRepository $baseProjectRepository;

    /**
     * @before
     */
    protected function setupRepository(): void
    {
        $git = new Git();
        $tmpDir = sys_get_temp_dir().'/'.uniqid(
            'dpp-composer-plugin-test',
            true
        );
        $this->baseProjectRepository = $git->init($tmpDir, ['-b' => '1.0.x']);

        file_put_contents(
            $this->baseProjectRepository->getRepositoryPath().'/composer.json',
            json_encode([
                'extra' => [
                    'drupal-lenient' => [
                        'drupal/d9-module',
                    ],
                ],
                'config' => [
                    'allow-plugins' => [
                        'allowed/plugin' => true,
                        'disallowed/plugin' => false,
                    ],
                ],
            ])
        );
        file_put_contents(
            $this->baseProjectRepository->getRepositoryPath().'/composer.lock',
            json_encode([
              'packages' => [
                [
                  'name' => 'drupal/core',
                  'version' => '10.0.0',
                  'dist' => [
                    'type' => 'zip',
                    'url' => 'https://api.github.com/repos/drupal/core/zipball/54415049a721ede65318e3980b402af59bc35913',
                    'reference' => '54415049a721ede65318e3980b402af59bc35913',
                    'shasum' => '',
                  ],
                ],
              ],
              'packages-dev' => [],
            ])
        );
        file_put_contents(
            $this->baseProjectRepository->getRepositoryPath().'/composer.patches.json',
            json_encode(['patches' => ['drupal/core' => ['My awesome patch' => 'i-do-stuff.patch']]])
        );
        $this->baseProjectRepository->addAllChanges();
        $this->baseProjectRepository->commit('Init repo');
        $this->baseProjectRepository->createTag('1.0.0');

        $this->baseProjectRepository->execute('checkout', '-b', '1.1.x');
        file_put_contents(
            $this->baseProjectRepository->getRepositoryPath().'/composer.lock',
            json_encode([
              'packages' => [
                [
                  'name' => 'drupal/core',
                  'version' => '10.1.0',
                  'dist' => [
                    'type' => 'zip',
                    'url' => 'https://api.github.com/repos/drupal/core/zipball/54415049a721ede65318e3980b402af59bc35913',
                    'reference' => '54415049a721ede65318e3980b402af59bc35913',
                    'shasum' => '',
                  ],
                ],
                [
                  'name' => 'drupal/smtp',
                  'version' => '1.4.2',
                ],
                [
                  'name' => 'symfony/translation-contracts',
                  'version' => '3.3.0',
                  'dist' => [
                    'type' => 'zip',
                    'url' => 'https://api.github.com/repos/symfony/translation-contracts/zipball/136b19dd05cdf0709db6537d058bcab6dd6e2dbe',
                    'reference' => '136b19dd05cdf0709db6537d058bcab6dd6e2dbe',
                    'shasum' => '',
                  ],
                ],
              ],
              'packages-dev' => [],
            ])
        );
        file_put_contents(
            $this->baseProjectRepository->getRepositoryPath().'/composer.patches.json',
            json_encode(['patches' => []])
        );
        $this->baseProjectRepository->addAllChanges();
        $this->baseProjectRepository->commit('Init repo');
        $this->baseProjectRepository->createTag('1.1.0');

        $this->baseProjectRepository->execute('checkout', '-b', 'update-1-1-x-75cd8bf8e4fc1c14433fd185b6c85b15');
    }

    /**
     * @after
     */
    public function tearDownGit(): void
    {
        exec('rm -rf '.$this->baseProjectRepository->getRepositoryPath());
    }
}
