<?php
declare(strict_types=1);

namespace RestApi\Test\TestCase\Model\Table;

use Cake\TestSuite\TestCase;
use RestApi\Model\ORM\InheritanceMarker;

class InheritanceMarkerTest extends TestCase
{
    public function testGetClassByType_shouldGetClass()
    {
        $marker = new InheritanceMarker();
        $marker->setMarkerField('group_id');
        $defaultClass = 'App\Model\Entity\User';
        $marker->addDefaultInheritedEntity($defaultClass);
        $chosenId = 4;
        $entityClass = 'App\Model\Entity\Seller';
        $marker->addInheritedEntity($chosenId, $entityClass);

        $this->assertEquals($entityClass, $marker->getClassByType($chosenId));
    }

    public function testGetClassByType_withDefault()
    {
        $marker = new InheritanceMarker();
        $marker->setMarkerField('group_id');
        $defaultClass = 'App\Model\Entity\User';
        $marker->addDefaultInheritedEntity($defaultClass);
        $entityClass = 'App\Model\Entity\Seller';
        $marker->addInheritedEntity(4, $entityClass);

        $trainerClass = 'App\Model\Entity\Seller';
        $this->assertEquals($defaultClass, $marker->getClassByType($trainerClass));
    }

    public function testGetClassByType_withoutDefault()
    {
        $marker = new InheritanceMarker();
        $marker->setMarkerField('group_id');
        $chosenId = 4;
        $entityClass = 'App\Model\Entity\Seller';
        $marker->addInheritedEntity($chosenId, $entityClass);

        $this->expectExceptionMessage('Invalid default marker value for group_id');
        $marker->getClassByType($chosenId);
    }
}
