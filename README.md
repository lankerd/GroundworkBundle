# Installation
Installation of the Groundwork Bundle requires that you edit the composer.json file directly instead of via the command line. Past the following code into your composer.json file and perform `composer update`

Add new repository to your composer.json:

```
"repositories": [{
    "type": "vcs",
    "url": "https://github.com/lankerd/GroundworkBundle",
    "options": {
        "http": {
            "header": [
                "API-TOKEN: 202baeb0a51e607da0bb4d39d56594e004f3c52d"
            ]
        }
    }
}],
```

Add to the "required" array:
`"lankerd/groundwork-bundle": "^1.*",`  

Update PHP Requirement:
`"php": "^7.2",`

# Usage
The groundwork bundle consist of the following advancements / enhancements to your symfony project:
* Advanced Email handling
* Template base framework
* Powerful CSV Import process
* Useful Twig Functions
* Other useful functionality

##### Advanced Email Handling

##### Template base framework

##### Powerful CSV Import Process
Make sure to add the code below:
```
    lankerd_groundwork:
        import_services:
        -   'user':
            -   'billing_address':
                -   'shipping_address':
                    -    'tank': ['tank_type']
 ```

##### Useful Twig Functions

##### Other useful functionality
To utilize many of the functionalities that the ground work bundle provides you need to do the following inside of your Entity file:

`use GroundworkEntityTrait;`

Example of how this would look in your file:

```
class Feed
{
    use GroundworkEntityTrait;
```
You will then be given access to the following methods:

``methodName($var, $var1)``


