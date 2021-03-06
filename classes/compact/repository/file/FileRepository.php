<?php
namespace compact\repository\file;

use compact\repository\IModelConfiguration;
use compact\repository\IModelRepository;
use compact\filesystem\Filesystem;
use compact\io\writer\StreamWriter;
use compact\io\reader\StreamReader;
use compact\mvvm\IModel;
use compact\logging\Logger;
use compact\repository\ISearchCriteria;
use compact\repository\impl\SearchCriteria;
use compact\utils\Random;

class FileRepository implements IModelRepository
{

    /**
     *
     * @var IModelConfiguration
     */
    private $configuration;

    /**
     *
     * @var StreamWriter
     */
    private $writer;

    /**
     *
     * @var StreamReader
     */
    private $reader;

    /**
     * Create a new FileModelRepository
     *
     * @param $aModelConfiguration IModelConfiguration            
     */
    public function __construct(IModelConfiguration $aModelConfiguration, \SplFileInfo $aFile)
    {
        $this->configuration = $aModelConfiguration;
        
        if (! $aFile->isFile()) {
            Filesystem::createFile($aFile);
        }
        
        $this->writer = new StreamWriter($aFile, StreamWriter::WRITEBINARY);
        $this->reader = new StreamReader($aFile);
    }

    /**
     * Factory method to create a new, empty model of the type this configuration can handle
     *
     * @return IModel
     */
    public function createModel()
    {
        return $this->getModelConfiguration()->createModel();
    }

    /**
     * (non-PHPdoc)
     *
     * @see compact\repository\IModelRepository::createSearchCriteria()
     */
    public function createSearchCriteria()
    {
        return SearchCriteria::create();
    }

    /**
     * Deletes the given model
     *
     * @param $aModel IModel
     *            The model to be deleted
     *            
     * @return boolean true when the delete was successfull, false when not
     */
    public function delete(IModel $aModel)
    {
        $pkField = $this->getModelConfiguration()->getKey();
        $store = $this->unserialize();
        if ($store->offsetExists($aModel->{$pkField})) {
            $store->offsetUnset($aModel->{$pkField});
            
            // rebuild array object to maintain indexes
            $newStore = new \ArrayObject();
            foreach ($store as $item) {
                $newStore->append($item);
            }
            
            Logger::get()->logFine("Delete model " . get_class($aModel) . ' pk: ' . $aModel->{$pkField});
            
            $this->serialize($newStore);
            
            return true;
        }
        return false;
    }

    /**
     * Returns the model configuration for this repository
     *
     * @return IModelConfiguration
     */
    public function getModelConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Returns the next (new) primary key
     *
     * @return int the new primary key
     */
    private function getNextKey(\ArrayObject $aStore, $keyName)
    {
        if ($keyName === "guid") {
            return Random::guid();
        } else {
            if ($aStore->count() === 0) {
                return 0;
            }
            
            $pk = 0;
            foreach ($aStore as $value) {
                $tmp = $value->{$keyName};
                
                if (is_numeric($tmp) && $tmp > $pk) {
                    $pk = $tmp;
                }
            }
            
            return $pk + 1;
        }
    }

    /**
     * Returns the underlying reader for subclasses
     *
     * @return \compact\io\reader\StreamReader
     */
    protected function getReader()
    {
        return $this->reader;
    }

    /**
     * Returns the underlying writer for subclasses
     *
     * @return \compact\io\writer\StreamWriter
     */
    protected function getWriter()
    {
        return $this->writer;
    }

    /**
     * (non-PHPdoc)
     *
     * @see compact\repository\IModelRepository::read()
     */
    public function read($aPk)
    {
        assert('$aPk !== null');
        $pkField = $this->getModelConfiguration()->getKey();
        $store = $this->unserialize();
        
        if ($store->offsetExists($aPk)) {
            $model = $store->offsetGet($aPk);
            
            Logger::get()->logFine("Read model " . get_class($model) . ' pk: ' . $aPk);
            
            return $model;
        }
        return null;
    }

    /**
     * (non-PHPdoc)
     *
     * @see compact\repository\IModelRepository::save()
     */
    public function save(IModel $aModel)
    {
        $config = $this->getModelConfiguration();
        $config->validate($aModel);
        
        $store = $this->unserialize();
        
        $pkField = $this->getModelConfiguration()->getKey();
        if (! isset($aModel->{$pkField}) || $aModel->{$pkField} === null) {
            $aModel->{$pkField} = $this->getNextKey($store, $pkField);
        }
        
        // insert a GUID when config has a guid
        if (in_array('guid', $config->getFieldNames($aModel)) && $aModel->isEmpty('guid')) {
            $aModel->set('guid', Random::guid());
        }
        
        // insert a timestamp when config has a guid
        if (in_array('timestamp', $config->getFieldNames($aModel)) && $aModel->isEmpty('timestamp')) {
            $aModel->set('timestamp', time());
        }
        
        // Check for update or insert
        $index = $this->guidExists($aModel->get('guid'), $store);
        if ($index === false) {
            // insert
            Logger::get()->logFine("Insert new model");
            $store->append($aModel);
        } else {
            // update
            Logger::get()->logFine("Update model " . $aModel->get('guid') . ' index: ' . $index);
            $store->offsetSet($index, $aModel);
        }
        
        Logger::get()->logFine("Save model " . get_class($aModel) . ' pk: ' . $aModel->{$pkField});
        
        return $this->serialize($store);
    }

    /**
     * Checks if the guid belongs the an existing model
     *
     * @param string $guid            
     * @param \ArrayObject $store            
     * @return mixed boolean index of the guid or false if not found
     */
    private function guidExists($guid,\ArrayObject $store)
    {
        foreach ($store as $key => $item) {
            Logger::get()->logFine("Check guid: " . $guid . ' - ' . $item->{'guid'});
            if ($item->{'guid'} === $guid) {
                Logger::get()->logFine("Found GUID " . $guid . ' = ' . $key);
                return $key;
            }
        }
        
        return false;
    }

    /**
     * (non-PHPdoc)
     *
     * @see compact\repository\IModelRepository::saveAll()
     */
    public function saveAll(\Iterator $aList)
    {
        $store = $this->unserialize();
        
        /* @var $model IModel */
        foreach ($aList as $model) {
            $this->getModelConfiguration()->validate($model);
            
            $pkField = $this->getModelConfiguration()->getKey();
            if (! isset($model->{$pkField}) || $model->{$pkField} === null) {
                $model->{$pkField} = $this->getNextKey($store, $pkField);
            }
            
            $store->offsetSet($model->{$pkField}, $model);
            
            Logger::get()->logFine("Save model " . get_class($model) . ' pk: ' . $model->{$pkField});
        }
        
        return $this->serialize($store);
    }

    /**
     * Returns all models in this repository
     *
     * @param $aSearchCriteria ISearchCriteria
     *            = null Optional search criteria
     *            
     * @return \ArrayIterator
     */
    public function search(ISearchCriteria $aSearchCriteria = null)
    {
        $store = $this->unserialize();
        $iter = $store->getIterator();
        
        if ($aSearchCriteria !== null) {
            $result = new \ArrayIterator();
            /* @var $model IModel */
            foreach ($iter as $model) {
                $i = 0;
                $match = true;
                foreach ($aSearchCriteria->getWhere() as $key => $value) {
                    if ($model->get($key) !== $value) {
                        $match = false;
                        break; // no match, continue with next model
                    }
                    $i ++;
                }
                
                if ($match) {
                    $result->append($model);
                }
            }
            
            if ($aSearchCriteria->getStartIndex() === null && $aSearchCriteria->getOffset() === null) {
                return $result;
            }
            
            return new \LimitIterator($result, $aSearchCriteria->getStartIndex(), $aSearchCriteria->getOffset());
        }
        
        return $iter;
    }

    /**
     * Serializes the ArrayObject store
     *
     * @param $aObject \ArrayObject            
     *
     * @return boolean true on success, false on failure
     */
    protected function serialize(\ArrayObject $aObject)
    {
        $serializeString = $aObject->serialize();
        $this->writer->open();
        $result = $this->writer->write($serializeString);
        $this->writer->close();
        
        Logger::get()->logFine('Serializing repository for ' . get_class($this->configuration) . '. Bytes: ' . strlen($serializeString) . ', models: ' . $aObject->count());
        
        return false != $result;
    }

    /**
     * Unserialize store from disk
     *
     * @return \ArrayObject
     */
    protected function unserialize()
    {
        $this->reader->open();
        $serializeString = "";
        while (! $this->reader->eof()) {
            $serializeString .= $this->reader->read(1024);
        }
        $this->reader->close();
        
        $object = new \ArrayObject();
        if (! empty($serializeString)) {
            $object->unserialize($serializeString);
            
            Logger::get()->logFine('Deserializing repository for ' . get_class($this->configuration) . '. Bytes: ' . strlen($serializeString) . ', models: ' . $object->count());
        }
        
        return $object;
    }
}