<?php

chdir(dirname(__FILE__));

require_once '../app/Mage.php';
Mage::app();
umask(0);

$customer = Mage::helper('customer');

print_r($customer->checkVatNumber('LU', '26375245', 'LU', '26375245')->getData());
