<?php

/*
 * Abstract class factory
 *
 * Can create classes
 */
interface AbstractClassFactory {
  public function create();
}

/*
 * An abstract container of items.
 * Kind of a "folder", or if you want, a class.
 *
 * You can put item into it.
 */
interface AbstractClass {

  /* Accept an item in this class */
  public function put($item);

  /*
   * Get the state of this class.
   *
   * Could be the array of all items, or an aggregated
   * value computed over them.
   */
  public function getState();
}

/*
 * Abstract classifier
 *
 * Receives items and organizes them in named classes.
 * Subclasses should override the getClassName($item)
 * method to find an appropriate class "name" for each
 * item.
 *
 * An AbstractClassFactory is used to create new named
 * classes. This factory is passed in the constructor.
 *
 * You can retrive the set of constructed classes
 * using the getClasses method.
 */
abstract class AbstractClassifier {
  protected $classes = array();
  

  protected function __construct(AbstractClassFactory $classFactory) {
    $this->classFactory = $classFactory;
  }

  /* Subclasses should override this */
  abstract public function put ($item);

  /* Returns a set of classes indexed by name */
  public function getClasses() {
    return $this->classes;
  }

  /* Subclasses should override this */
  //abstract protected function getClassName($item);

  /* Subclasses should override this */
  abstract protected function getClassNames($item);

}

/*
 * This classifier implements a single-pass classification,
 * that is it expects to be able to tell which classes
 * belong to which given item at first look.
 *
 * Subclases should implement the getClassNames($item) method
 * returning an array of class names for the given item.
 */
abstract class AbstractSinglePassClassifier extends AbstractClassifier {

  public function __construct(AbstractClassFactory $classFactory) {
    parent::__construct($classFactory);
  }

  public function put ($item) {
    $names = $this->getClassNames($item);

    foreach ($names as $name) {
      if (empty($name)) {
        $name = 'Unknown';
      }
      if ( ! isset($this->classes[$name]) ) {
        $this->classes[$name] = $this->classFactory->create();
      }
      $this->classes[$name]->put($item);
    }
  }

}

/*
 * Abstract value extractor
 *
 * Can extract values from items
 */
interface AbstractExtractor {
  public function extract($item);
}

/*
 * Classifies by single extracted value
 *
 */
class SingleExtractedValueClassifier extends AbstractSinglePassClassifier {

  private $extractor = null;

  public function __construct(AbstractClassFactory $classFactory,
      AbstractExtractor $extractor)
  {
    parent::__construct($classFactory);
    $this->extractor = $extractor;
  }

  protected function getClassNames($item) {
    $classnames = array(
      $this->extractor->extract($item)
    );
    return $classnames;
  }


}

/*
 * Counter aggregator/class
 */
class CounterClass implements AbstractClass {
  private $count = 0;
  public function put($item) {
    $this->count++;
  }

  public function getState() {
    return $this->count;
  }
}

/*
 * Counter aggregator/class factory
 */
class CounterClassFactory implements AbstractClassFactory {
  public function create() {
    return new CounterClass();
  }
}

/*
 * Abstract summer aggregator/class 
 * Performs a sum of the values of items
 */
abstract class ValueSummerClass implements AbstractClass {
  private $sum = 0;

  public function put($item) {
    $this->sum += $this->getItemValue($item);
  }

  public function getState() {
    return $this->sum;
  }

  /* Subclasses should override this */
  abstract public function getItemValue ($item);

}

/*
 * Extracted value summer aggregator/class 
 *
 * Performs a sum on the values of items as extracted
 * by an AbstractExtractor function.
 *
 * The extractor function is passed in the constructor.
 * Construct these classes using ExtractedValueSummerClassFactory
 */
class ExtractedValueSummerClass extends ValueSummerClass {

  private $extractor = null;

  public function __construct(AbstractExtractor $extractor) {
    $this->extractor = $extractor;  
  }

  /* Subclasses should override this */
  public function getItemValue ($item) {
    return $this->extractor->extract($item);
  }

}

/*
 * Creates classes that perform a sum of the values extracted
 * by the given AbstractExtractor function.
 */
class ExtractedValueSummerClassFactory implements AbstractClassFactory {

  private $extractor = null;

  public function __construct(AbstractExtractor $extractor) {
    $this->extractor = $extractor;  
  }

  public function create() {
    return new ExtractedValueSummerClass($this->extractor);
  }
}

/*
 * Extracts an object property value
 */
class ObjectPropValueExtractor implements AbstractExtractor {
  private $fieldname = null;

  function __construct($fieldname) {
    $this->fieldname = $fieldname;
  }

  function extract($item) {
    return $item->{$this->fieldname};
  }
}

/*
 * Classifier aggregator/class
 */
class ClassifyingClass implements AbstractClass {
  private $classifier = null;

  public function __construct(AbstractClassifier $classifier) {
    $this->classifier = $classifier;
  }

  public function put($item) {
    $this->classifier->put($item);
  }

  public function getState() {
    return $this->classifier->getClasses();
  }
}

/*
 * Classifier aggregator/class factory
 */
class ClassifyingClassFactory implements AbstractClassFactory {
  private $template = null;

  public function __construct(AbstractClassifier $template) {
    $this->template = $template;
  }

  public function create() {
    $copy = clone $this->template;
    $classifyingClass = new ClassifyingClass($copy);
    return $classifyingClass;
  }
}

