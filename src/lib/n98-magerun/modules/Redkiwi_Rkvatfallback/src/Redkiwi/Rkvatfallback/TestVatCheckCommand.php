<?php

namespace Redkiwi\Rkvatfallback;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestVatCheckCommand extends AbstractMagentoCommand
{
    /**
     * Set up Magerun command
     */
    protected function configure()
    {
        $this
            ->setName('vatfallback:test')
            ->setDescription('Test VAT fallback with configured flow');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output);
        if (!$this->initMagento()) return;

        $customer = \Mage::helper('customer');

        $output->writeln('Testing valid number LU26375245');
        $result = print_r($customer->checkVatNumber('LU', '26375245', 'LU', '26375245')->getData(), true);
        $output->writeln($result);

        $output->writeln('Testing invalid number NL123456789B01');
        $result = print_r($customer->checkVatNumber('NL', '123456789B01', 'LU', '26375245')->getData(), true);
        $output->writeln($result);
    }
}