<?php

namespace Drupal\Tests\common\Kernel\FileFetcher;

use Drupal\common\FileFetcher\FileFetcherFactory;
use Drupal\KernelTests\KernelTestBase;
use FileFetcher\FileFetcher;
use Drupal\common\FileFetcher\FileFetcherRemoteUseExisting;
use FileFetcher\Processor\Remote;
use Procrastinator\Result;

/**
 * @covers \Drupal\common\FileFetcher\FileFetcherFactory
 * @coversDefaultClass \Drupal\common\FileFetcher\FileFetcherFactory
 *
 * @group dkan
 * @group common
 * @group kernel
 */
class FileFetcherFactoryTest extends KernelTestBase {

  protected const DATA_FILE_URL = 'https://dkan-default-content-files.s3.amazonaws.com/phpunit/district_centerpoints_small.csv';

  protected static $modules = [
    'common',
  ];

  public function provideUseExisting() {
    return [
      'use existing' => [TRUE, FileFetcherRemoteUseExisting::class],
      'do not use existing' => [FALSE, Remote::class],
    ];
  }

  /**
   * @dataProvider provideUseExisting
   *
   * @see \Drupal\Tests\datastore\Kernel\Service\ResourceLocalizerTest::testLocalizeOverwriteExistingLocalFile()
   */
  public function testOurRemote($use_existing, $remote_class) {
    // Config for overwrite.
    $this->installConfig(['common']);
    $config = $this->config('common.settings');
    $config->set('always_use_existing_local_perspective', $use_existing);
    $config->save();

    /** @var \Drupal\common\FileFetcher\FileFetcherFactory $factory */
    $factory = $this->container->get('dkan.common.file_fetcher');
    $this->assertInstanceOf(FileFetcherFactory::class, $factory);

    // Set up an existing file.
    $tmp = sys_get_temp_dir();
    $dest_file_path = $tmp . '/' . basename(self::DATA_FILE_URL);
    $dest_file_contents = 'not,the,source,contents';

    // Put some known contents in the existing file.
    file_put_contents($dest_file_path, $dest_file_contents);

    // Get a FileFetcher instance using our config.
    $config = [
      'filePath' => self::DATA_FILE_URL,
      'temporaryDirectory' => $tmp,
    ];
    $ff = $factory->getInstance('identifier', $config);
    $this->assertInstanceOf(FileFetcher::class, $ff);

    // Make sure we have the correct processor class that corresponds to our
    // config.
    $this->assertEquals($remote_class, $ff->getState()['processor']);

    // Did it work?
    $result = $ff->run();
    $this->assertEquals(Result::DONE, $result->getStatus(), $result->getError());

    // Same file path, even if it's not the same contents?
    $this->assertEquals($dest_file_path, $ff->getStateProperty('destination'));

    // Depending on the config, the contents should or should not match.
    if ($use_existing) {
      // Same contents?
      $this->assertEquals($dest_file_contents, file_get_contents($dest_file_path));
    }
    else {
      $this->assertNotEquals($dest_file_contents, file_get_contents($dest_file_path));
    }
  }

}
