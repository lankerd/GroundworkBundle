<?php

namespace Lankerd\GroundworkBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GroundworkTableDeleteCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('groundwork:table:delete')
            ->setDescription('Used in order to empty out a single table')
            ->addArgument('tableName', InputArgument::REQUIRED, 'The desired table name, for example: "user_address"');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tableName = (string) $input->getArgument('tableName');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $connection = $em->getConnection();
        $connection->beginTransaction();
        try {
            $connection->query('SET FOREIGN_KEY_CHECKS=0');

            $connection->query('DELETE FROM :tableName');
            $connection->query('ALTER TABLE :tableName AUTO_INCREMENT = 1');
            $connection->query('SET FOREIGN_KEY_CHECKS=1');
            $connection->bindValue(':'.$tableName, $tableName);
        } catch (\Exception $e) {
            $connection->rollback();
        }

        $output->writeln('Purged '.$tableName);
    }
}
