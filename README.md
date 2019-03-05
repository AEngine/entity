AEngine Entity
==== 

Some parts of the project were influenced by: Laravel, Symfony Framework's. Thank you!

#### Requirements
* PHP >= 7.0

#### Installation
Run the following command in the root directory of your web project:
> `composer require aengine/entity`

#### Usage
Create an `index.php` file with the following contents:

```php
class Car extends Model {
    public $brand = '';
    public $mark = '';
    public $color = '';
}

$cars = collect([
    new Car(['brand' => 'BMW', 'mark' => 'M4', 'color' => 'red']),
    new Car(['brand' => 'BMW', 'mark' => 'X5', 'color' => 'yellow']),
    new Car(['brand' => 'Peel', 'mark' => 'P50', 'color' => 'blue']), // Peel Engineering Company
]);

var_dump($cars);

```

#### Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

#### License
The AEngine Entity is licensed under the MIT license. See [License File](LICENSE.md) for more information.
