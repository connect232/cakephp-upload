# Upload plugin for CakePHP

## Installation
Run:
```
composer require connect232/cakephp-upload
```

## Usage
Run : `bin/cake plugin load Upload`
Or in your `src/Application.php` add:
```
$this->addPlugin('Upload');
```
In your model `initialize()`:
```
$this->addBehavior('Upload.Upload', [
	'your_field_name' => [
			'path' => 'directory/to/save/file/to'
		]
	]
);
```
If you have baked your model remove the scalar validation:
```
$validator->scalar('foo');
```
Modify your form to accept files
```
$this->Form->create($foo, ['type' =>' file']);
```
Modify your field's type to file
```
$this->Form->control($foo, ['type' => 'file']);
```
