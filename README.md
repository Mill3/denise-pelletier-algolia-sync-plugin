

## Plugin structure :

Namespace : WpAlgolia

* Main plugin class
  * init Algolia client instance
  * set supported post-type
* WpAlgolia/Register/PostTypeAbstract (abstract)
* WpAlgolia/Register/PostTypeInterface (interface)
* WpAlgolia/Register/Posts (class implements RegisterInterface)
* WpAlgolia/Register/Programs (class implements RegisterInterface)