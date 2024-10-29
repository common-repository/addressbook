#!/bin/bash
xgettext --language=PHP \
         --keyword=__ \
         --keyword=_e \
         --output=addressbook.pot \
         --copyright-holder='Sam Wilson' \
         --msgid-bugs-address=sam@co-operista.com \
         addressbook.php
