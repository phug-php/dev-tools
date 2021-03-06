<?php

namespace Phug\Test\DevTool;

use PHPUnit\Framework\TestCase;
use Phug\DevTool\Application;
use Phug\DevTool\Command\UnitTestsRunCommand;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\NullOutput;

/**
 * Class UnitTestsRunCommandTest.
 *
 * @coversDefaultClass \Phug\DevTool\Command\UnitTestsRunCommand
 */
class UnitTestsRunCommandTest extends TestCase
{
    private static function remove($entity)
    {
        if (is_file($entity)) {
            return unlink($entity);
        }

        foreach (scandir($entity) as $file) {
            if ($file !== '.' && $file !== '..') {
                self::remove($entity.'/'.$file);
            }
        }
    }

    /**
     * @covers ::configure
     */
    public function testConfigure()
    {
        $unitTests = new UnitTestsRunCommand();

        self::assertSame('unit-tests:run', $unitTests->getName());
    }

    /**
     * @covers ::execute
     */
    public function testExecute()
    {
        $cwd = getcwd();
        $app = realpath(__DIR__.'/../../../app');
        chdir($app);
        foreach (glob(__DIR__.'/../../../app/vendor/bin/*') as $file) {
            chmod($file, 0777);
        }
        $coverageHtml = $app.DIRECTORY_SEPARATOR.'coverage';
        $coverageClover = $app.DIRECTORY_SEPARATOR.'coverage.xml';
        $input = new StringInput(
            'unit-tests:run'.
            ' --coverage-text'.
            ' --coverage-html='.escapeshellarg(addslashes($coverageHtml)).
            ' --coverage-clover='.escapeshellarg(addslashes($coverageClover))
        );
        $buffer = new NullOutput();
        $app = new Application();
        $app->setAutoExit(false);
        ob_start();
        $code = $app->run($input, $buffer);
        $contents = ob_get_contents();
        ob_end_clean();
        self::assertSame(0, $code);
        $data = json_decode($contents);
        self::assertTrue($data->{'--coverage-text'});
        self::assertSame($coverageHtml, $data->{'--coverage-html'});
        self::assertSame($coverageClover, $data->{'--coverage-clover'});
        if (file_exists($coverageHtml)) {
            self::remove($coverageHtml);
        }
        if (file_exists($coverageClover)) {
            unlink($coverageClover);
        }
        chdir($cwd);
    }
}
