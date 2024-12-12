<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\System\Core\Console;


use \Symfony\Component\Console\Command\Command;
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableCell;
use \Symfony\Component\Console\Helper\TableSeparator;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Style\SymfonyStyle;


/**
 * Module CLI.
 * 
 * @since 1.1.3
 */
class Constants extends BaseConsole
{


    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setName('system:constants')
            ->setDescription('Display constants for use with other programming languages.')
            ->setHelp(
                'Display constants for use with other programming languages'."\n\n".
                '[Display all constants]' . "\n" .
                '  To display all constants, run the command without the option.' . "\n" .
                '  Example: ' . "\n" .
                '    system:constants' . "\n" .
                '      This will show all user\'s constants.' . "\n\n" .
                '[Display selected constant group]' . "\n" .
                '  To display selected constant group, enter the --group option.' . "\n" .
                '  Example: ' . "\n" .
                '    system:constants --group="user"' . "\n" .
                '      This will show selected constant names and value of selected group.' . "\n\n" .
                '[Display selected constant]' . "\n" .
                '  To display selected constant name, enter the --name option.' . "\n" .
                '  Example: ' . "\n" .
                '    system:constants --name="APP_ENV"' . "\n" .
                '      This will show selected constant name and value. If it was not found, the "Undefined constant" message will be displayed.' . "\n\n"
            )
            ->addOption('group', null, InputOption::VALUE_OPTIONAL, 'The constant group. (See more at https://www.php.net/manual/en/function.get-defined-constants.php ).')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'The constant name.');
    }// configure


    /**
     * Convert constant value to string.
     * 
     * @param mixed $constantValue
     * @return string
     */
    private function convertConstantValueString($constantValue): string
    {
        if (!is_string($constantValue) && !is_scalar($constantValue)) {
            $constantValue = trim(var_export($constantValue, true));
        }

        return $constantValue;
    }// convertConstantValueString


    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $Input, OutputInterface $Output): int
    {
        $Io = new SymfonyStyle($Input, $Output);
        $optionGroup = $Input->getOption('group');
        $optionName = $Input->getOption('name');

        if (
            (
                $optionGroup === '' ||
                is_null($optionGroup)
            ) &&
            (
                $optionName === '' ||
                is_null($optionName)
            )
        ) {
            // if no group and no name specific.
            // list all constants.
            $this->executeAllConstants($Input, $Output);
        } elseif (
            (
                $optionName !== '' &&
                !is_null($optionName)
            )
        ) {
            // if there is specific "name" (constant name).
            $this->executeSpecificConstantName($Input, $Output, $optionName);
        } elseif (
            (
                $optionGroup !== '' &&
                !is_null($optionGroup)
            )
        ) {
            // if there is sepcific "group" (constant group).
            $this->executeSpecificConstantGroup($Input, $Output, $optionGroup);
        } else {
            $this->executeAllConstants($Input, $Output);
        }// endif; action.

        unset($Io, $optionGroup, $optionName);

        if (defined('Command::SUCCESS')) {
            return Command::SUCCESS;
        } else {
            return 0;
        }
    }// execute


    /**
     * Display all constants.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     */
    private function executeAllConstants(InputInterface $Input, OutputInterface $Output)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Table = new Table($Output);
        $Table->setColumnMaxWidth(0, 20);
        $Table->setColumnMaxWidth(1, 30);
        $Io->title('Display all user\'s constants');

        $allConstants = get_defined_constants(true);

        $Table->setHeaders(['Constant name', 'Value']);
        if (is_array($allConstants)) {
            foreach ($allConstants as $group => $items) {
                $Table->addRows([
                    new TableSeparator(),
                    [new TableCell($group, ['colspan' => 2])],
                    new TableSeparator()
                ]);

                if (is_array($items)) {
                    foreach ($items as $constantName => $constantValue) {
                        $constantValue = $this->convertConstantValueString($constantValue);
                        
                        $Table->addRows([
                            [$constantName, $constantValue]
                        ]);
                    }// endforeach; $items
                    unset($constantName, $constantValue);
                }
            }// endforeach; $allConstants
            unset($group, $items);
        }
        unset($allConstants);
        $Table->render();

        unset($Io, $Table);
    }// executeAllConstants


    /**
     * Display specific constant group.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     * @param string $optionGroup
     */
    private function executeSpecificConstantGroup(InputInterface $Input, OutputInterface $Output, string $optionGroup)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Table = new Table($Output);
        $Table->setColumnMaxWidth(0, 20);
        $Table->setColumnMaxWidth(1, 30);
        $Io->title('Display selected constant group');

        $allConstants = get_defined_constants(true);

        $Table->setHeaders(['Constant name', 'Value']);
        if (is_array($allConstants)) {
            foreach ($allConstants as $group => $items) {
                if ($group === $optionGroup) {
                    $Table->addRows([
                        new TableSeparator(),
                        [new TableCell($group, ['colspan' => 2])],
                        new TableSeparator()
                    ]);

                    if (is_array($items)) {
                        foreach ($items as $constantName => $constantValue) {
                            $constantValue = $this->convertConstantValueString($constantValue);

                            $Table->addRows([
                                [$constantName, $constantValue]
                            ]);
                        }// endforeach; $items
                        unset($constantName, $constantValue);
                    }

                    break;
                }// endif;
            }// endforeach; $allConstants
            unset($group, $items);
        }
        unset($allConstants);
        $Table->render();

        unset($Io, $Table);
    }// executeSpecificConstantGroup


    /**
     * Display specific constant name.
     * 
     * @param InputInterface $Input
     * @param OutputInterface $Output
     * @param string $optionName
     */
    private function executeSpecificConstantName(InputInterface $Input, OutputInterface $Output, string $optionName)
    {
        $Io = new SymfonyStyle($Input, $Output);
        $Io->title('Display selected constant');

        if (defined($optionName)) {
            $constantValue = $this->convertConstantValueString(constant($optionName));
            $Io->writeln($optionName . ' = ' . $constantValue);
            unset($constantValue);
        } else {
            $Io->error('Undefined constant (' . $optionName . ')');
        }// endif;

        unset($Io);
    }// executeSpecificConstantName


}
