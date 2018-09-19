<?php

namespace Lankerd\GroundworkBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ImportCommand extends ContainerAwareCommand
{
//    protected $importPath;
//
//    /**
//     * ComprehensiveImportCommand constructor.
//     *
//     * @param $importPath
//     */
//
//    public function __construct($importPath)
//    {
//        $this->importPath = $importPath;
//        parent::__construct();
//    }

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('groundwork:import:all')
            // the short description shown while running "php bin/console list"
            ->setDescription('Imports all records.');
            // the full command description shown when running the command with
            // the "--help" option
//            ->setHelp(
//                'This command imports all of the records that will be sent from , to this application.'
//            )
            // configure an argument
//            ->addArgument(
//                'import_path',
//                $this->importPath ? InputArgument::OPTIONAL : InputArgument::REQUIRED,
//                'Where is the full import path located?',
//                $this->importPath
//            );
//            ->addArgument(
//                'statement_type',
//                $this->statementType ? InputArgument::OPTIONAL : InputArgument::REQUIRED,
//                'Which type of statement will we be expecting? (PAMS/ASCEND)',
//                $this->statementType
//            );
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
//        $statementType = $input->getArgument('statement_type');

        /*Grab the CSV Directory*/
        $importPath = $this->getContainer()->getParameter('import_directory');

        /*Cut out '..' and '.' when we scan the csv directory, effectively grabbing [all] file [name(s)] in the process*/
        $filesToImport = array_diff(scandir($importPath), array('.', '..'));
        //dump($this->);
        die;
        $this->getContainer()->get('user.model.layout')->makeUsers($importPath, $filesToImport);
    }
}
