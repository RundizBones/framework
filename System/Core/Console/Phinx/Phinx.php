<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Core\Console\Phinx;


/**
 * Phinx CLI
 * 
 * @since 0.1
 * @link https://github.com/cakephp/phinx/issues/856 Ideas
 */
class Phinx
{


    /**
     * @var \Phinx\Console\PhinxApplication
     */
    protected $PhinxApplication;


    /**
     * Class constructor.
     * 
     * @param \Rdb\System\Container $Container The DI container class.
     */
    public function __construct(?\Rdb\System\Container $Container = null)
    {
        $this->PhinxApplication = new \Phinx\Console\PhinxApplication();
    }// __construct


    /**
     * Include external commands
     * 
     * @param \Symfony\Component\Console\Application $CliApp The Symfony application class to use `addCommands()` method.
     */
    public function IncludeExternalCommands(\Symfony\Component\Console\Application $CliApp)
    {
        $phinxCommands = [];
        foreach ($this->PhinxApplication->all() as $commandName => $command) {
            if (strtolower($commandName) !== 'help' && strtolower($commandName) !== 'list') {
                $phinxCommands[] = $command->setName('phinx:'.str_replace(':', '-', $commandName));
            }
        }
        $CliApp->addCommands($phinxCommands);
    }// IncludeExternalCommands


}
