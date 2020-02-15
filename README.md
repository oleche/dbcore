dbcore
======
[![CircleCI](https://circleci.com/gh/oleche/dbcore.svg?style=svg&circle-token=45f4c4a0b4c32cc66de8689377f298fb2fe7190d)](https://circleci.com/gh/oleche/dbcore) [![Latest Stable Version](https://poser.pugx.org/geekcow/dbcore/v/stable)](https://packagist.org/packages/geekcow/dbcore) [![Total Downloads](https://poser.pugx.org/geekcow/dbcore/downloads)](https://packagist.org/packages/geekcow/dbcore)

A basic and simple ORM engine for PHP. This is still a working progress tool, but It has been successfully implemente in a cople of sites and an API. *Once they get public, they will be shared here*

## Basic Usage
1. Extend the Entity object in your own entity:
```
class Test extends Entity{
  private $name_of_the_table = [
      'key' => [ 'type' => 'int', 'unique' => true, 'pk' => true ],
      'name' => [ 'type' => 'string', 'length' => 32, 'unique' => true ]
  ];

  public function __construct(){
    parent::__construct($this->name_of_the_table, get_class($this));
  }
}
```
2. Use the entity class to perform database operations:
```
$test = new Test();
$result = $test->fetch();
```
To fetch from an ID:
```
$test->fetch_id(array('key'=>'1'));
```
To insert:
```
$test->columns['key'] = 0;
$test->columns['name'] = 'test entry';
$id = $test->insert();
```
To update:
```
$test->fetch_id(array('key'=>'1'));
$test->columns['name'] = 'new name';
$test->update();
```
To delete:
```
$test->fetch_id(array('key'=>'1'));
$test->delete();
```
## Methods

## Configuration
It is required to have a config.ini file. There is an attached demo file.

## Composer
For installing it using composer, just:
```
composer require geekcow/dbcore
```
NOTE: Always remember to autoload. A good usage example is in ``` src/demo/demo-composer.php ```

## Demo
Use the demo-implementation.php file. Run to test it by:
``` php demo-implementation.php ```

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Add some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request
