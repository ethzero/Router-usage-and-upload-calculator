# Router usage and upload calculator
====================================
Couple of notes:

* This is a rough alpha,
* This has a great name so shut up :)

What is it?
-----------
The goal is to make a simple to read Internet bandwidth monitor for end users.
It requires your router to have the SNMP service enable to gather stats and a webserver to host the main PHP page.


Erm, is that it?
----------------
Well yup, so far the code is more "single propose, that'll do for me" grade.
The intent is to keep this as compact as possible, not some complex nagios/mrtg-like thing.

Known bugs
----------
If you leave the page open in Chrome for over 24 hours you may see a crash.
(I think this is due to the horrible way I'm instancing the gauges)


