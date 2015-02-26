<?php
namespace Samurai\Composer;

use InvalidArgumentException;
use Samurai\Composer\Config\ComposerConfigManager;
use TRex\Cli\Executor;

/**
 * Class Composer
 * @package Samurai\Composer
 * @author Raphaël Lefebvre <raphael@raphaellefebvre.be>
 */
class Composer
{
    /**
     * @var Project
     */
    private $project;

    /**
     * @var ComposerConfigManager
     */
    private $composerConfigManager;

    /**
     * @var Executor
     */
    private $executor;

    /**
     * @param Project $project
     * @param Executor $executor
     */
    public function __construct(Project $project, Executor $executor)
    {
        $this->setProject($project);
        $this->setComposerConfigManager(new ComposerConfigManager());
        $this->setExecutor($executor);
    }

    /**
     * Getter of $project
     *
     * @return Project
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param array $options
     * @return string
     * @throws InvalidArgumentException
     */
    public function createProject(array $options = array())
    {
        if(!$this->getProject()->getBootstrapName()){
            throw new InvalidArgumentException('The bootstrap of the project is not defined');
        }

        return $this->getExecutor()->flush(
            trim(
                sprintf(
                    'composer create-project %s %s %s',
                    $this->getProject()->getBootstrapName(),
                    $this->getProject()->getDirectoryPath(),
                    $this->getProject()->getBootstrapVersion()
                )
            )
            .$this->mapOptions($options)
        );
    }

    /**
     * @return string
     */
    public function getConfigPath()
    {
        $dirPath = $this->getProject()->getDirectoryPath();
        if($dirPath){
            $dirPath = rtrim($dirPath, '/') . '/';
        }
        return  $dirPath . 'composer.json';
    }

    /**
     * @return array|null
     */
    public function getConfig()
    {
        return $this->getComposerConfigManager()->get($this->getConfigPath());
    }

    /**
     * @return bool
     */
    public function validateConfig()
    {
        return $this->getExecutor()->flush($this->cd() . 'composer validate');
    }

    /**
     * @return int
     */
    public function resetConfig()
    {
        $config = $this->getConfig();
        if($config===null){
            throw new \RuntimeException(sprintf(
                'Impossible to load the composer config from file "%s"',
                $this->getConfigPath()
            ));
        }

        $config = array_merge($config, $this->getProject()->toConfig());
        $config = $this->cleanConfig($config);

        return $this->getComposerConfigManager()->set($this->getConfigPath(), $config);
    }

    /**
     * @param $config
     * @return array
     */
    private function cleanConfig($config)
    {
        if (array_key_exists('version', $config)) {
            unset($config['version']);
        }
        if (array_key_exists('time', $config)) {
            unset($config['time']);
        }

        $recursiveFilter = function($value, callable $filter){
            if(is_array($value)){
                $result = [];
                foreach($value as $key => $subValue) {
                    if($filter($value[$key], $filter)) {
                        $result[$key] = $subValue;
                    }
                }
                return $result;
            }
            return $value;
        };

        return $recursiveFilter($config, $recursiveFilter);
    }

    /**
     * @return string
     */
    private function cd()
    {
        if($this->getProject()->getDirectoryPath()) {
            return 'cd ' . $this->getProject()->getDirectoryPath() . ' && ';
        }
        return '';
    }

    /**
     * @param array $options
     * @return string
     */
    private function mapOptions(array $options)
    {
        $result = '';
        foreach($options as $option => $value){
            $result .= ' --' . $option . '=' . $value;
        }
        return $result;
    }

    /**
     * Setter of $project
     *
     * @param Project $project
     */
    private function setProject(Project $project)
    {
        $this->project = $project;
    }

    /**
     * Getter of $composerConfigManager
     *
     * @return ComposerConfigManager
     */
    public function getComposerConfigManager()
    {
        return $this->composerConfigManager;
    }

    /**
     * Setter of $composerConfigManager
     *
     * @param ComposerConfigManager $composerConfigManager
     */
    public function setComposerConfigManager(ComposerConfigManager $composerConfigManager)
    {
        $this->composerConfigManager = $composerConfigManager;
    }

    /**
     * Getter of $executor
     *
     * @return Executor
     */
    private function getExecutor()
    {
        return $this->executor;
    }

    /**
     * Setter of $executor
     *
     * @param Executor $executor
     */
    private function setExecutor(Executor $executor)
    {
        $this->executor = $executor;
    }
}
