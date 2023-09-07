# eCHIS-user-manager
## user management script for data alignment for uploading users on eCHIS

1. Remove any formatting and capital letters from original csv file before extracting it. Maintain csv headers to required text format in `config.php`
2. make sure php 8 library is installed globally on machine
3. Add the csv spreadsheet to the script.php folder
4. Open the cleaned csv file on a code editor(sublime text or vs code) to check for spaces between records, at the beginning and end of the document. As well as unsupported text formats(apostrophes , asterix, amperstand)
5. Add the name of the csv on the `path` name in `config.php`
6. Add `uuid code` from the county instance you newly created
7. Add instantiate instance variable with live instance url
8. use `php script.php` to run the script
9. Check for `csv folder` and `user_list.csv` created in root folder.
