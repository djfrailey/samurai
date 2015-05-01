<?php
namespace Samurai\Project\Question;

use Pimple\Container;
use Puppy\Config\Config;
use Samurai\Alias\AliasManagerFactory;
use Samurai\Project\Project;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;

/**
 * Class BootstrapQuestionTest
 * @package Samurai\Project\Question
 * @author Raphaël Lefebvre <raphael@raphaellefebvre.be>
 */
class BootstrapQuestionTest extends \PHPUnit_Framework_TestCase
{
    public function testExecuteEmpty()
    {
        $input = $this->provideInput([]);
        $output = new BufferedOutput();
        $services = $this->provideServices();

        $question = new BootstrapQuestion($services);
        $this->assertTrue($question->execute($input, $output));
        $this->assertSame('raphhh/php-lib-bootstrap', $services['project']->getBootstrapName());
        $this->assertSame('', $services['project']->getBootstrapVersion());
    }

    public function testExecuteValid()
    {
        $input = $this->provideInput(['bootstrap' => 'vendor/package']);
        $output = new BufferedOutput();
        $services = $this->provideServices();

        $question = new BootstrapQuestion($services);
        $this->assertTrue($question->execute($input, $output));
        $this->assertSame('vendor/package', $services['project']->getBootstrapName());
        $this->assertSame('', $services['project']->getBootstrapVersion());
    }

    public function testExecuteWithVersion()
    {
        $input = $this->provideInput(['bootstrap' => 'vendor/package', 'version' => '1.0.0']);
        $output = new BufferedOutput();
        $services = $this->provideServices();

        $question = new BootstrapQuestion($services);
        $this->assertTrue($question->execute($input, $output));
        $this->assertSame('vendor/package', $services['project']->getBootstrapName());
        $this->assertSame('1.0.0', $services['project']->getBootstrapVersion());
    }

    /**
     * @return Container
     */
    private function provideServices()
    {
        $services = new Container();
        $services['project'] = function () {
            return new Project();
        };

        $services['alias_manager'] = function () {
            $factory = new AliasManagerFactory();
            return $factory->createFromConfig(new Config(''));
        };

        $questionHelper = $this->getMock('Symfony\Component\Console\Helper\QuestionHelper', array('ask'));

        $questionHelper->expects($this->any())
            ->method('ask')
            ->will($this->returnCallback(function(InputInterface $input, OutputInterface $output, ChoiceQuestion $question){
                $choices = $question->getChoices();
                return current($choices);
            }));

        $services['question'] = function () use ($questionHelper){
            return $questionHelper;
        };

        return $services;
    }

    /**
     * @param array $args
     * @return ArrayInput
     */
    private function provideInput(array $args)
    {
        return new ArrayInput(
            $args,
            new InputDefinition([
                new InputArgument('bootstrap'),
                new InputArgument('version')
            ])
        );
    }
}
