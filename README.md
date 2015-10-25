# mbrowser
Php Drop In To Enhance Local Filebrowsing Experience

### basic usage
* what it does (so far) is visual pimp up filebrowsing
* filling the gap between file-explorer and media-center/server.
* php has to be installed with imagehandling gd2 enabled
* mkdir ".browse" in rootlevel of filebase|collection|whatever
* put src directory ".browse" in your folder ".browse"
* edit .browse/.browse/global.ini
* goto .browse/.browse/system/{your OS}/  and execute the run-script
* on firstrun index.php will be copied to ./browse
* the localhost suffucient buildin php server ist started
* and the default browser should launch
* navigate into one of your folders and try the search
* if all goes well a shiny nice thematic layout should appear
* try localhost?research to build a custom layout for each rootlevel folder
* async call for support-file-search is on schedule