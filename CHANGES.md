CHANGES
===========

1.0.2
-----------

* Create logger in Application (not in Dispatcher)
* Create renderer object when it is needed.
* Includes fixes a little bugs and refactors.

1.0.1
-----------

* Support HTTP 451 status (Thanks @zonuexe #2).
* Fix: Controller::redirect() method sent wrong headers (Thanks @b-kaxa #5).
* Fix: Response class is now LoggerAware (#6).
* Includes fixes a little bugs and refactors.
