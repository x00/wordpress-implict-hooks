# Wordpress Implicit Hooks #

Is a plugin framework for Wordpress influenced by a mixture of Vanilla/Garden and Symfony's style of extensibility, but designed to be lightweight and embeddable.

Released under MIT. 

## About ##

Implicit Hooks allow you to develop Wordpress plugins via special methods in hook classes, and a bunch of other neat stuff.

This means you don't need to explicitly register hooks, as they are implied. 

Feature include:

- Super lightweight embedded framework
- Simple to use installer for your projects
- Templates such as MVC and basic, and you can add your own
- Flexible but not strictly enforced
- Organised with as many hooks classes as you wish
- Readable syntax with custom labels
- Action, filter, activate and deactivate type hooks
- You never have to specify number of arguments
- Parallel development, self evident
- Explicitly routed, dependency injection through services file
- Extensibility through parrarel pluggablity and via overriding services
- Easy access to Wordpress objects, libraries and services

## Installation ##

First cd to your home folder, or wherever you like to install scripts, then run

    $ git clone https://github.com/x00/wordpress-implict-hooks
    $ cd wordpress-implict-hooks

For help on how to run the installer run

    $ php install.php --help

If you want a MVC pattern for your project run

    $ php install.php --template=mvc /path/to/wordpress/wp-content/plugins/special-widget SpecialWidget
    
Subsituting `/path/to/wordpress/wp-content/plugins/` with to the directory you are developing plugins in, `special-widget` for the plugin folder and `SpecialWidget` as the plugin class prefix.
    
The project folder can be new or existing, so long as the directory it is in exists. 

For a more basic installation run

    $ php install.php /path/to/wordpress/wp-content/plugins/special-widget SpecialWidget
    
Don't forget to update your plugin file header with your own information!
    
## Updates ##

To update the framework installer run

    $ git pull origin master
    
To update your project use:

    $ install.php --update /path/to/wordpress/wp-content/plugins/special-widget SpecialWidget
    
This will only replace the framework and the loader file \(`your-plugin-name-loader.php`\), unless you specify a template.

## Hooks Files ##

These are files that go in the `hooks` directory. The files are named by the convention `class-something-hooks.php`, where `something` is a name of your choice. 

The class should extend your project alias of Implicit Hooks, which is the plugin class prefix and ending in `Hooks`, as should the class itself \(also prefixed\).

Example:

    class SpecialWidgetSomethingHooks extends SpecialWidgetHooks {
        ...
    }
    
The actual implicit hooks are special public methods contained in hooks files, named in the form:

    method_label__wordpress_hook__(action|filter)[_priority_number]
    
You can specify them as an action or filter, generally actions output and filters return something filtered.

The priority number is optional. No need to specify number of arguments as this is obvious to the framework.

Examples:

    public function i18n__plugins_loaded__action() {
        $this->load_language();
    }
    
    public function approval__pre_comment_approved__filter( $approved, $commentdata ) {
        // do something
        return $approved;
    }
    
`load_language` could for instance contain a `load_plugin_textdomain` call or whatever, however you could employ service methods \(described bellow\) for more complex stuff, which can keep your hooks files clean. 

You also have activation and deactivation hooks like so:

    public function migrate__register__activate() {
        // anything to setup?
        
        // do migration ? 
    }
    
    public function clean_up__register__deactivate() {
        // anything to wind down or remove? 
    }


## Services ##

In the `config` folder of your project you will have a `services.php` file.

Inside the file you will see a `$services` array, which will be used by framework.

It is optional to use services but they are useful for dependency injection and organisation. 

You can specify a service like so:

    'service_name' => array(
        'class' => 'ClassName',
        'path'  => 'folder/class-classname.php'
    )
    
If you wish to pass arguments specify `args` like so:

        'args'  => array(
            'arg_name_1' => '%%some_global_object',
            'arg_name_2' => '@@another_service'
            ...
        )
        
Where `arg_name_...` is whatever name you want to use. Note the special `%%` and `@@` syntax for objects and services respectively. 
        
If you want to reference array items or object properties, etc. Use syntax dot \(`.`\) like so:

            'arg_name_3' => %%some_global_object.property_is_array.array_index
            
If you have a library which cannot be passed as an injected instance, but still needed, first make a service of it, then use a `requires` array on the service that requires it, like so:

        'requires' => array(
            'some_library_service',
        )
        
Then you can instance the class when you wish or static methods. 

Your service classes need to have a single argument constructor, as service arguments are passed as an array for simplicity.

Example:

    class SpecialWidgetUtility extend SpecialWidgetPluggable {
        
        protected $wp_query;
        
        public function __construct( $args ) {
            $this->wp_query = $args['wp_query'];
        }
        
    }
    
You can also access service methods in your hooks files like so

    $this->services('service_name')->method_name();
    
Extending with the included Pluggable class is not strictly necessary, but a nice touch. 
    
### Extensions ###

You can add event use the `action` / `filter` methods in your services and hooks files, which take an arbitrary number of arguments after the event name. Service files require inheritance of Pluggable class for this to work. 
    
To extend services via another plugin use the `implicit_hooks_services` hook, it is passed the services array, and the plugin class prefix you need for reference.

You can require the original class and extend it. 
    
## Contribution and Issues ##

Pull requests are welcome. I am trying to keep this a very light weight framework, so that is the philosophy to bear in mind. 

Any help with documentation or annotation of templates is also very much appreciated. 

Implicit Hooks can ship with a small handful of templates, which might need improvement and annotation.

I don't want to support a huge number as anyone is free to make their own and release them, as templates can simply be added to the templates folder.

For bugs use the [https://github.com/x00/wordpress-implict-hooks/issues](issue tracker)

## Template Structure and Format ##

Use existing templates for guidance. 

You **need** to include a `hooks` folder.

The service file `config/service.php` is not required but recommended. 

There following text is used for substitution by the installer:

- `PLUGIN_PATH` constant, you might occasionally use this in your services file (e.g. `views_dir` argument) or hooks. 
- `plugin-folder` name of the pluign folder and plugin file.
- `PluginPrefix` the plugin class prefix, essential for Hooks and Services to prevent collision.
- `plugin_label` is a variable/function safe prefix based on the pluign folder which probably won't be need that often e.g `$plugin_label_something`.
 
So be carefull about how you name things in you templates and test with the installer. 

## Fans ##

Keen to hear about those using the project. Examples, no doubt, will help others.

----
Copyright 2015 © Paul Thomas
