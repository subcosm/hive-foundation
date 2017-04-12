<?php
/**
 * This file is part of the subcosm.
 *
 * (c)2017 Matthias Kaschubowski
 *
 * This code is licensed under the MIT license,
 * a copy of the license is stored at the project root.
 */

namespace Subcosm\Tests\Hive\Mocks;


use Subcosm\Hive\HiveInterface;
use Subcosm\Observatory\ObservationContainerInterface;
use Subcosm\Observatory\ObserverInterface;

class MockedObserver implements ObserverInterface
{
    public $primitiveStatus = false;

    /**
     * method that will be invoked when an update has occurred at the observable.
     *
     * @param ObservationContainerInterface $container
     * @return void
     */
    public function update(ObservationContainerInterface $container): void
    {
        if ( $container->isStage(HiveInterface::GET_STAGE) ) {
            $this->primitiveStatus = true;
        }
    }

}
