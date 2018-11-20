<?php

namespace Lankerd\GroundworkBundle\Command;

use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class GroundworkImportCommand extends ContainerAwareCommand
{
    protected $services;

    protected function configure()
    {
        $this
            ->setName('groundwork:import:all')
            ->setDescription('Imports all records.')
            ->setDefinition(
                new InputDefinition(array(
                    new InputOption('empty_tables', 'c', InputOption::VALUE_OPTIONAL),
                ))
            );
//            ->addArgument('empty_tables', InputArgument::OPTIONAL, 'Would you like to empty all listed tables in the config file?');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     * @throws \Exception
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {

        $emptyTablesOption = $input->getOption('empty_tables');
        /* We will initially run some removal scripts (if any are present)*/


        if (strstr('yes', $emptyTablesOption)) {
            $tablesToDelete = $this->getContainer()->getParameter('foreign_key_tables_to_delete');
            if (!empty($tablesToDelete)) {
                $command = $this->getApplication()->find('groundwork:table:delete');

                foreach ($tablesToDelete as $tableToDelete) {
                    $arguments = array(
                        'command'   => 'groundwork:table:delete',
                        'tableName' => $tableToDelete
                    );

                    $commandInput = new ArrayInput($arguments);
                    $command->run($commandInput, $output);
                }
            }
        }
        /*Grab the CSV Directory from the configuration file*/
        $importPath = $this->getContainer()->getParameter('import_directory');
        /*Cut out '..' and '.' when we scan the csv directory, effectively grabbing [all] file [name(s)] in the process*/
        $filesToImport = array_diff(scandir($importPath), array('.', '..'));

        /*Grab all of the services that will be unpacked*/
        $services = $this->getContainer()->getParameter('lankerd_groundwork.import_services');

        $trimmedFilesToImport =[];
        $fileNames = [];
        /*We'll set a global that's watching our service listing[s]*/
        foreach ($filesToImport as $key => $fileToImport) {
            if (!empty(strpos($fileToImport, '.csv'))){
                /*Strip the extension off of the filename*/
                $fileNames[] = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileToImport);
                $trimmedFilesToImport[] = $fileToImport;
            }
        }
        $this->services = $fileNames;

        $this->processServices($services, $importPath, $trimmedFilesToImport);

    }

    public function processServices($services, $importPath, $filesToImport)
    {
        foreach ($services as $service) {
            if (is_array($service)) {
                $this->runServices(key($service), $importPath, $filesToImport);
                $this->processServices($service, $importPath, $filesToImport);
            }else{
                $this->runServices($service, $importPath, $filesToImport);
            }
        }
    }

    /**
     * @param $service
     * @param $importPath
     * @param $filesToImport
     */
    private function runServices($service, $importPath, $filesToImport)
    {
        if ($service == 'user') {
            $this->getContainer()->get('user.model.layout')->makeUsers($filesToImport);
            $this->getContainer()->get('user.model.layout')->setOptions(
                [
                    'filesToImport'  => $filesToImport,
                    'importPath'     => $importPath,
                    'serviceListing' => $this->services
                ]
            );
        } else {
            foreach ($filesToImport as $key => $fileToImport) {
                /*Strip the extension off of the filename in order to run the file in it's correct */
                $filename = preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileToImport);
                try {
                    $this->getContainer()->get($filename);
                } catch (\Exception $e) {
                    unset($filesToImport[$key]);
                    continue;
                }
                /*Let's remove the oncoming file*/
                unset($filesToImport[$key]);
                if ($service == $filename) {
                    echo "\n=============$filename=============\n";
                    $this->getContainer()
                        ->get($service)
                        ->setOptions(
                            [
                                'filesToImport'  => $filesToImport,
                                'importPath'     => $importPath,
                                'serviceListing' => $this->services,
                                'currentService' => $service
                            ]
                        );
                    $this->getContainer()
                        ->get($service)
                        ->readCSV($importPath.$fileToImport);
                }
            }
        }
    }
}
