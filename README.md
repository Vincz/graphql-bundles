# New architecture proposal

## The configuration parsers

The configuration parsers (yaml, xml, annotations, attributes & graphql) extract schema configuration from files or metadatas.  
They provide `Configuration` (`src/Configuration/Configuration.php`) objects. They are grouped into 3 bundles:  
- GraphQLConfigurationGraphQLBundle  (experimental)
- GraphQLConfigurationYamlXmlBundle
- GraphQLConfigurationMetadataBundle

The parsers grouping is based on common features. 
What changed: 
    - All the inheritance stuff will now be part of the Yaml / Xml bundle


So without shortcut, a Yaml configuration would look like that:
```yaml
MyType:
    description: Description of my type
    fields:
        field1: 
            type: String
            extensions: 
                - { alias: "access", configuration: "@=IsAuthenticated()" }
    extensions:
        - { alias: "builder", configuration: { name: "MyFieldsBuilder" } }
```
or with annotations: 
```php
/**
 * @GQL\Type
 * @GQL\Description("Description of my type")
 * @GQL\Extension("builder", { name: "MyFieldsBuilder" })
 */
class MyType {
    /** 
     * @GQL\Field
     * @GQL\Extension("access", "@=IsAuthenticated()")
     */
    protected string $field1;
}
```

It seems more verbose at first, but extensions will be able to define their own annotations or Yaml key to ease even further the configuration.  


## The configuration object

The `Configuration` object is a PHP object holding the GraphQL configuration of types.  
For each type, it holds the required GraphQL configuration needed and an array of extension configurations.  

## The configuration Provider (`src/ConfigurationProvider/ConfigurationProvider.php` )

The configuration provider will take every `Configuration` objects provided by the parsers and merge them into a single `Configuration` object.  
So it is now possible to mix types from any parsers and `Configuration` can be provided from anywhere as long as it's from a service with the DI tag `overblog_graphql.configuration.provider`.
After the merge, the Configuration provider will validate the final `Configuration` object: 
- Check the validity of names
- Check the validity of types
- Check the validation of extensions configuration
- Check the duplication according to 3 possibles strategies:
  - Forbidden: It is not possible to define two types (or fields, or args, ...) with the same name.
  - Override same type: It is possible to override a type if it is of the same GraphQL type
  - Override all: It is possible to override any type

When an override strategy is used, the latest defined type erase the previous one.  This allow to redefine type from other Configuration providers.  

What changed:
    - The configuration validation is now part of the `Configuration` object. It is no longer the validation of PHP array.  

## The extensions system

An extension can be define by extending `src/Extension/Extension.php`.  
Extensions must have a **unique alias** define by a constant `ALIAS` on the extension class.  
They must be tagged with the tag `overblog_graphql.extension`.  
An extension can support any of the basic types (`Object`, `Interface`, `Field`, `Argument`, etc...).  
An extension must provide a `TreeBuilder` to validate properly his configuration  
An extension will allow to hook into different parts of the GraphQL process.  
1. At the `configuration` level : The extension will be able to alter the Configuration before the final validation. Allowing to add or modify type (ex: Builder, Crud, etc...).
2. At the `building` level : The extension will be able to alter the generated class in the cache.  
3. At the `resolvers` level : The extension will be able to add middlewares to the resolver chains (ex: Access, Cache, etc...).

What changed: 
`Access`, `Public`, `Builder`, `Relay`, `Complexity` should now be define as Extension (in their own bundle?).

### Extension: BuilderExtension

The new builder extension provide feature to handle builders (with legacy builders support).  

# Why is it better ?

- We don't deal with array of array of config anymore.  
- The configuration process is now the `Symfony way` with services and stuff and can be extended as needed.  
- We have a basic GraphQL bundle (framework?) handling configuration & extension system and that's it, the rest is optionnal.
- It's way more easy to add features to the bundle for the community. 


# What needs to be done

## TypeBuilder rewrite

The TypeBuilder needs to be rewrite to use `TypeConfiguration` instead of PHP config array.  
It also needs to support extensions (API to be defined).

## Yaml/Xml Parser

The yaml parser is ok-ish and would need some cleanup and love.  
It would also need to support extension shortcut registration to allow for example: 

```yaml
MyType:
    fields:
        fields1: 
            type: String
            _access: "@IsAuthenticated()"
```
instead of the verbose example of the first paragraqh. Note that the same extension may be applied multiple times to the same object.  

## Extensions

We need to write the `Access`, `IsPublic`, `Complexity` and `Relay` extension. 

## Tests

A bunch of tests have been added for the new configuration and extension systems, but we need more.  
We also need to move the old tests into their new bundle:  
    - All the inheritance tests should move into the YamlXml bundle.  
    - The tests related to an extension should move into the extension bundle.  
    - etc...

## Resolvers

We also need to come up with a solution to handle resolvers more properly (with Middlewares support).  
Given the fact that resolvers must be set in configuration, the only way I can think of is a string like `ServiceName` or `ServiceName::MethodName`.  
Like Symfony controllers at the end. 