# Router usage and upload calculator
====================================
Couple of notes:

* This is a rough alpha,
* This has a great name so shut up :)

What is it?
-----------
The goal is to provide a simple near real-time Internet bandwidth monitor.

It uses a combination of the router's SNMP service and MRTG log files to gather device capabilities and read logged stats.
It requires that this be hosted on a PHP capable webserver with the PHP-SNMP libs and MRTG already configured 

YMMV
----
So far the code is more "single propose, that'll do for me" grade.
I'll be gradually working this up to something a little more patellable and user configuable.

Known bugs
----------
If you leave the page open in Chrome for over 24 hours you may see a crash.
(I think this is due to the horrible way I'm instancing the gauges)


