droplr-export
=============

Now that Droplr is going all-paid, here's a way to get back your own data.

## How to use

1. Fetch a JSON file of your drops from Droplr's web interface (you may need 
   a Chrome extension like Dev HTTP Client that uses your existing cookies). 
   The endpoint is `https://droplr.com/drops?type=all&amount=100&offset=0&sortBy=creation&order=asc`.
   Your JSON request must include the header `Accept: application/json`.
2. Execute the `droplr-export.php` script by invoking it from the command line 
   or running it from a web server (untested but should work):
   - **CLI:** Invoke the script as 
        `php droplr-export.php PATH_TO_JSON [PATH_TO_SAVE]`
   where `PATH_TO_JSON` is the location of your JSON data from step 1, and 
   `PATH_TO_SAVE` is an optional directory in which to save your files. (You 
   can also give **droplr-export.php** executable permissions and use it directly.)
   - **Web:** Customize the variables `$filename` and `$destination` to 
   your needs, and load the script in a browser. Note that this is untested, 
   and therefore unsupported.