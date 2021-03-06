<?php
namespace compact\repository\json;

use testutils\mvvm\TestModelConfiguration;
use compact\utils\Random;
use compact\logging\Logger;
use testutils\mvvm\TestModel;

/**
 * Test class for JsonRepository.
 * Generated by PHPUnit on 2015-01-02 at 11:50:54.
 */
class JsonRepositoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var JsonRepository
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new JsonRepository(new TestModelConfiguration(), new \SplFileInfo(sys_get_temp_dir() . '/compact-' . Random::alphaNum(10) . '.json'));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
    
    /**
     * @covers compact\repository\json\JsonRepository::createModel
     */
    public function testCreateModel()
    {
        $model = $this->object->createModel();
    
        $this->assertTrue($model instanceof \compact\mvvm\IModel, 'Got class: ' . get_class($model));
    }
    
    /**
     * (non-PHPdoc)
     *
     * @covers compact\repository\json\JsonRepository::save()
     * 
     * @return IModel to be used in other tests
     */
    public function testSaveInsert(){
        $model = $this->object->createModel();
        TestModel::randomData($model);
        
        $this->assertTrue( $this->object->save($model),  'Saving TestModel failed' );
        $this->assertTrue(is_numeric($model->get(TestModel::ID)), 'PrimaryKey not filled in ' . $model->get(TestModel::ID));
        
        return $model;
    }
    
    /**
     * (non-PHPdoc)
     *
     * @covers compact\repository\json\JsonRepository::save()
     *
     * @return IModel to be used in other tests
     */
    public function testSaveUpdate(){
        
        $model1 = $this->testSaveInsert();
        $model2 = $this->testSaveInsert();
        
        $this->assertEquals(2, $this->object->search()->count(), "Expected to find 2 models");
        
        // update
        $model2->set(TestModel::FIELD1, "field1");
        $this->object->save($model2);
        
        // check
        $models = $this->object->search();
        $this->assertEquals(2, $models->count(), "Expected to find 2 models");
        
        $this->assertEquals("field1", $models->offsetGet(1)->get(TestModel::FIELD1));
    }
    
    /**
     * (non-PHPdoc)
     *
     * @covers compact\repository\json\JsonRepository::read()
     */
    public function testRead(){
        $model = $this->testSaveInsert();
        
        $read = $this->object->read($model->get(TestModel::ID));
        
        $this->assertTrue($model instanceof \compact\mvvm\IModel, 'Got class: ' . get_class($read));
        $this->assertEquals($read->get(TestModel::ID), $model->get(TestModel::ID));
        $this->assertEquals($read->get(TestModel::NUMBER), $model->get(TestModel::NUMBER));
        $this->assertEquals($read->get(TestModel::FIELD1), $model->get(TestModel::FIELD1));
        $this->assertEquals($read->get(TestModel::FIELD2), $model->get(TestModel::FIELD2));
    }
    
    /**
     * (non-PHPdoc)
     *
     * @covers compact\repository\json\JsonRepository::search()
     */
    public function testDelete(){
        // add 3 models
        $model1 = $this->testSaveInsert();
        $model2 = $this->testSaveInsert();
        $model3 = $this->testSaveInsert();
    
        $models = $this->object->search();
        $this->assertEquals($models->count() , 3, 'Found ' . $models->count() . ' models instead of 3' );
        
        // Delete model 2
        $this->object->delete($model2);
        
        // check for models 1 and 3
        $models = $this->object->search();
        $this->assertEquals($models->count() , 2, 'Found ' . $models->count() . ' models instead of 2' );
        
        $this->assertEquals($model1->get(TestModel::ID), $models->offsetGet(0)->get(TestModel::ID));
        $this->assertEquals($model3->get(TestModel::ID), $models->offsetGet(1)->get(TestModel::ID));
    }
    
    /**
     * (non-PHPdoc)
     *
     * @covers compact\repository\json\JsonRepository::search()
     */
    public function testSearch(){
        // add 4 models
        $this->testSaveInsert();
        $this->testSaveInsert();
        $this->testSaveInsert();
        $this->testSaveInsert();
        
        $models = $this->object->search();
        
        $this->assertEquals($models->count() , 4, 'Found ' . $models->count() . ' models instead of 4' );
    }
}
