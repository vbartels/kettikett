# kettikett

This Project aims at easy price tag printing of multiple pricetags for korona cloud POS systems.

It runs on a webserver (like WinNMP https://winnmp.wtriple.com/) and is used via a VERY simple webinterface.

Just scan all the eans you need new pricetags for in the Textfield.

Im using:
https://github.com/kreativekorp/barcode
and
http://www.fpdf.org/

Both are included in this git, you may want to update them by yourself.

Usage:
edit settings.php file and add your korona api credentials and choose your font by pointing to a font file.
Open index.php
Already generated pricetags are saved individually in tmp folder. You might want to clean it up manually.
All "work" is done inside getarticles.php.


Known Issues:
ketikett currently gets all articles out of the backend per print.. this takes some time and need fpr ~15.000 articles up to over a minute. This is not elegant.. a local cache would save bandwidth and fast things up. However it works as it is...

Disclaimer:
this was hacked together by me after not writing any code for ~15years. Sorry 
